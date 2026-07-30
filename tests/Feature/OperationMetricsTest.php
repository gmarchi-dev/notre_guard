<?php

namespace Tests\Feature;

use App\Models\ChecklistItem;
use App\Models\ChecklistTemplate;
use App\Models\IncidentType;
use App\Models\Unit;
use App\Services\OperationMetrics;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Feature\Api\SyncTestCase;

/**
 * Indicadores do painel. Erro aqui não quebra nada - só mostra um número
 * errado, que é pior: alguém decide com base nele.
 */
class OperationMetricsTest extends SyncTestCase
{
    private function metrics(?int $unitId = null, int $days = 30): OperationMetrics
    {
        return OperationMetrics::forPeriod(
            Carbon::today()->subDays($days - 1),
            Carbon::today(),
            $unitId,
        );
    }

    /** Uma ronda com $scanned de 2 pontos. */
    private function patrol(int $scanned, ?string $incidentDescription = null): void
    {
        $shiftUuid = (string) Str::uuid7();
        $patrolUuid = (string) Str::uuid7();

        $events = [
            $this->event('shift.start', ['post_id' => $this->post->id], uuid: $shiftUuid),
            $this->event('patrol.start', ['shift_uuid' => $shiftUuid, 'patrol_route_id' => $this->route->id], uuid: $patrolUuid),
        ];

        for ($i = 0; $i < $scanned; $i++) {
            $events[] = $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[$i]->id,
                'latitude' => (float) $this->checkpoints[$i]->latitude,
                'longitude' => (float) $this->checkpoints[$i]->longitude,
            ]);
        }

        if ($incidentDescription) {
            $events[] = $this->event('incident.report', [
                'shift_uuid' => $shiftUuid,
                'incident_type_id' => IncidentType::firstOrCreate(['name' => 'Portão aberto'])->id,
                'description' => $incidentDescription,
                'severity' => 'high',
            ]);
        }

        $events[] = $this->event('patrol.end', ['patrol_uuid' => $patrolUuid]);
        $events[] = $this->event('shift.end', ['shift_uuid' => $shiftUuid]);

        $this->sync($events)->assertOk();
    }

    public function test_adherence_is_scanned_over_expected(): void
    {
        $this->patrol(scanned: 2); // 2 de 2
        $this->patrol(scanned: 1); // 1 de 2

        // 3 lidos de 4 previstos.
        $this->assertSame(75.0, $this->metrics()->adherence());
    }

    public function test_adherence_is_null_without_patrols(): void
    {
        // Zero por cento e "não houve ronda" são coisas diferentes: o gráfico
        // não pode desenhar uma queda onde não houve operação.
        $this->assertNull($this->metrics()->adherence());
    }

    public function test_counts_separate_incomplete_patrols(): void
    {
        $this->patrol(scanned: 2);
        $this->patrol(scanned: 1);

        $counts = $this->metrics()->patrolCounts();

        $this->assertSame(2, $counts['total']);
        $this->assertSame(1, $counts['incomplete']);
    }

    public function test_metrics_are_isolated_by_unit(): void
    {
        $this->patrol(scanned: 1, incidentDescription: 'Ocorrência da unidade de teste');

        $otherUnit = Unit::create(['name' => 'Outra', 'code' => 'OUT']);

        $this->assertSame(1, $this->metrics($this->unit->id)->patrolCounts()['total']);
        $this->assertSame(0, $this->metrics($otherUnit->id)->patrolCounts()['total']);
        $this->assertSame(0, $this->metrics($otherUnit->id)->incidentCounts()['total']);
        $this->assertNull($this->metrics($otherUnit->id)->adherence());
    }

    public function test_period_excludes_older_records(): void
    {
        $this->patrol(scanned: 2);

        // Período de ontem para trás não contém a ronda de hoje.
        $past = OperationMetrics::forPeriod(
            Carbon::today()->subDays(10),
            Carbon::yesterday(),
            null,
        );

        $this->assertSame(0, $past->patrolCounts()['total']);
        $this->assertSame(1, $this->metrics()->patrolCounts()['total']);
    }

    public function test_day_series_covers_every_day_and_marks_gaps_as_null(): void
    {
        $this->patrol(scanned: 1);

        $series = $this->metrics(days: 7)->adherenceByDay();

        $this->assertCount(7, $series);
        $this->assertEquals(50, $series[Carbon::today()->toDateString()]);
        $this->assertNull($series[Carbon::today()->subDays(3)->toDateString()], 'dia sem ronda deve ficar vazio');
    }

    public function test_incidents_are_grouped_by_hour(): void
    {
        $this->patrol(scanned: 1, incidentDescription: 'Portão destrancado');

        $byHour = $this->metrics()->incidentsByHour();

        $this->assertCount(24, $byHour);
        $this->assertSame(1, $byHour[(int) now()->hour]);
        $this->assertSame(1, array_sum($byHour));
    }

    public function test_top_nonconforming_checkpoints_is_ranked(): void
    {
        $template = ChecklistTemplate::create(['unit_id' => $this->unit->id, 'name' => 'Perímetro']);
        $item = ChecklistItem::create(['checklist_template_id' => $template->id, 'label' => 'Portão trancado']);

        $shiftUuid = (string) Str::uuid7();
        $patrolUuid = (string) Str::uuid7();

        $events = [
            $this->event('shift.start', ['post_id' => $this->post->id], uuid: $shiftUuid),
            $this->event('patrol.start', ['shift_uuid' => $shiftUuid, 'patrol_route_id' => $this->route->id], uuid: $patrolUuid),
        ];

        // Dois apontamentos no PC-01 e um no PC-02.
        foreach ([0, 0, 1] as $index) {
            $events[] = $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[$index]->id,
                'checklist' => [[
                    'uuid' => (string) Str::uuid7(),
                    'checklist_item_id' => $item->id,
                    'answer' => 'nonconforming',
                    'note' => 'Encontrado aberto',
                ]],
            ]);
        }

        $this->sync($events)->assertOk();

        $top = $this->metrics()->topNonConformingCheckpoints();

        $this->assertStringStartsWith('PC-01', $top->first()->label);
        $this->assertSame(2, $top->first()->total);
        $this->assertSame(1, $top->last()->total);
    }

    public function test_deviation_rate_counts_scans_not_deviations(): void
    {
        // Uma leitura pode carregar vários desvios; a taxa é de leituras, não
        // de marcas, senão ela passa de 100%.
        $this->patrol(scanned: 2);

        $shiftUuid = (string) Str::uuid7();
        $patrolUuid = (string) Str::uuid7();

        $this->sync([
            $this->event('shift.start', ['post_id' => $this->post->id], uuid: $shiftUuid),
            $this->event('patrol.start', ['shift_uuid' => $shiftUuid, 'patrol_route_id' => $this->route->id], uuid: $patrolUuid),
            $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[0]->id,
                // Sem coordenada e fora de ordem: dois desvios, uma leitura.
            ]),
        ])->assertOk();

        $rate = $this->metrics()->scanDeviationRate();

        $this->assertNotNull($rate);
        $this->assertLessThanOrEqual(100, $rate);
        $this->assertEqualsWithDelta(33.3, $rate, 0.1, '1 leitura com desvio em 3');
    }
}
