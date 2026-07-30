<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\User;
use App\Services\DailyReportBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Feature\Api\SyncTestCase;

/**
 * O RDO é o documento que a supervisão e o contratante consomem. Ele agrega o
 * que veio do campo - por isso os testes constroem os dados pelo mesmo caminho
 * que o aparelho usa, e não por factory.
 */
class DailyReportTest extends SyncTestCase
{
    private DailyReportBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = app(DailyReportBuilder::class);
        Storage::fake();
    }

    /** Executa um turno completo com uma ronda parcial e uma ocorrência. */
    private function runShift(bool $closeShift = true): void
    {
        $shiftUuid = (string) Str::uuid7();
        $patrolUuid = (string) Str::uuid7();

        $events = [
            $this->event('shift.start', ['post_id' => $this->post->id], uuid: $shiftUuid),
            $this->event('patrol.start', ['shift_uuid' => $shiftUuid, 'patrol_route_id' => $this->route->id], uuid: $patrolUuid),
            $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[0]->id,
                'latitude' => -22.90560,
                'longitude' => -47.06080,
            ]),
            $this->event('incident.report', [
                'shift_uuid' => $shiftUuid,
                'incident_type_id' => \App\Models\IncidentType::create(['name' => 'Portão aberto'])->id,
                'description' => 'Portão dos fundos encontrado destrancado.',
                'severity' => 'high',
                'classification' => 'prevention',
                'location' => 'Portão dos fundos',
            ]),
            $this->event('patrol.end', ['patrol_uuid' => $patrolUuid]),
        ];

        if ($closeShift) {
            $events[] = $this->event('shift.end', ['shift_uuid' => $shiftUuid, 'handover_notes' => 'Portão comunicado à manutenção.']);
        }

        $this->sync($events)->assertOk();
    }

    public function test_report_aggregates_the_day(): void
    {
        $this->runShift();

        $report = $this->builder->buildOrUpdate($this->unit, Carbon::today());
        $summary = $report->summary;

        $this->assertSame('draft', $report->status);
        $this->assertSame(1, $summary['shifts']['total']);
        $this->assertSame('Portão comunicado à manutenção.', $summary['shifts']['items'][0]['handover_notes']);

        $this->assertSame(1, $summary['patrols']['total']);
        $this->assertSame(1, $summary['patrols']['incomplete'], 'leu 1 de 2 pontos');
        // Comparação frouxa: o número volta do JSON do banco como int.
        $this->assertEquals(50, $summary['patrols']['adherence']);

        $this->assertSame(1, $summary['incidents']['total']);
        $this->assertSame('RO 001/'.now()->year, $summary['incidents']['items'][0]['number']);
        $this->assertSame(['high' => 1], $summary['incidents']['by_severity']);
    }

    public function test_closing_seals_the_content_and_produces_a_pdf(): void
    {
        $this->runShift();

        $report = $this->builder->buildOrUpdate($this->unit, Carbon::today());
        $closed = $this->builder->close($report, User::factory()->create(['role' => User::ROLE_SUPERVISOR]));

        $this->assertSame('closed', $closed->status);
        $this->assertNotNull($closed->closed_at);
        $this->assertSame(64, strlen($closed->content_hash));

        Storage::assertExists($closed->pdf_path);
        $this->assertGreaterThan(1000, strlen(Storage::get($closed->pdf_path)), 'PDF vazio ou truncado');
    }

    public function test_cannot_close_while_a_shift_is_still_open(): void
    {
        // Fechar com turno aberto produz um RDO que nasce desatualizado.
        $this->runShift(closeShift: false);

        $report = $this->builder->buildOrUpdate($this->unit, Carbon::today());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('turno(s) aberto(s)');

        $this->builder->close($report, User::factory()->create(['role' => User::ROLE_ADMIN]));
    }

    public function test_closed_report_is_not_recalculated(): void
    {
        $this->runShift();

        $report = $this->builder->buildOrUpdate($this->unit, Carbon::today());
        $closed = $this->builder->close($report, User::factory()->create(['role' => User::ROLE_ADMIN]));
        $sealedTotal = $closed->summary['patrols']['total'];

        $this->runShift();

        $again = $this->builder->buildOrUpdate($this->unit, Carbon::today());

        $this->assertSame('closed', $again->status);
        $this->assertSame($sealedTotal, $again->summary['patrols']['total'], 'o RDO fechado é fotografia, não espelho');
    }

    public function test_late_records_are_detected_against_the_seal(): void
    {
        // Aparelho que passou dias sem rede: os registros chegam depois do
        // fechamento e o documento deixa de refletir a realidade.
        $this->runShift();

        $report = $this->builder->buildOrUpdate($this->unit, Carbon::today());
        $closed = $this->builder->close($report, User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->assertFalse($this->builder->hasLateRecords($closed));

        $this->runShift();

        $this->assertTrue($this->builder->hasLateRecords($closed->refresh()));
    }

    public function test_pdf_download_is_blocked_for_another_unit(): void
    {
        $this->runShift();

        $report = $this->builder->close(
            $this->builder->buildOrUpdate($this->unit, Carbon::today()),
            User::factory()->create(['role' => User::ROLE_ADMIN]),
        );

        $otherUnit = \App\Models\Unit::create(['name' => 'Outra', 'code' => 'OUT']);

        $intruder = User::factory()->create([
            'role' => User::ROLE_UNIT_MANAGER,
            'unit_id' => $otherUnit->id,
        ]);

        $this->actingAs($intruder)->get(route('rdo.pdf', $report))->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERVISOR]))
            ->get(route('rdo.pdf', $report))
            ->assertOk();
    }

    public function test_report_is_unique_per_unit_and_date(): void
    {
        $this->runShift();

        $first = $this->builder->buildOrUpdate($this->unit, Carbon::today());
        $second = $this->builder->buildOrUpdate($this->unit, Carbon::today());

        $this->assertTrue($first->is($second));
        $this->assertSame(1, DailyReport::count());
    }
}
