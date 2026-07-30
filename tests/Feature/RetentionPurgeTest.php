<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\ChecklistResponse;
use App\Models\DailyReport;
use App\Models\Incident;
use App\Models\IncidentType;
use App\Models\Patrol;
use App\Models\PatrolScan;
use App\Models\RetentionRun;
use App\Models\Shift;
use App\Models\SyncBatch;
use App\Models\User;
use App\Services\DailyReportBuilder;
use App\Services\RetentionPurger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Feature\Api\SyncTestCase;

/**
 * Expurgo é código destrutivo: o que ele apaga não volta. Os testes cobrem
 * tanto o que precisa sair quanto - principalmente - o que precisa ficar.
 */
class RetentionPurgeTest extends SyncTestCase
{
    private RetentionPurger $purger;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        $this->purger = app(RetentionPurger::class);
    }

    /**
     * Cria um turno com ronda, leitura, checklist e evidência, na data pedida.
     */
    private function shiftAt(Carbon $when, bool $withEvidence = true): Shift
    {
        $shift = Shift::create([
            'uuid' => (string) Str::uuid7(),
            'security_guard_id' => $this->guard->id,
            'post_id' => $this->post->id,
            'unit_id' => $this->unit->id,
            'started_at' => $when,
            'started_received_at' => $when,
            'ended_at' => $when->copy()->addHours(8),
            'ended_received_at' => $when->copy()->addHours(8),
            'status' => 'closed',
        ]);

        $patrol = Patrol::create([
            'uuid' => (string) Str::uuid7(),
            'shift_id' => $shift->id,
            'patrol_route_id' => $this->route->id,
            'unit_id' => $this->unit->id,
            'started_at' => $when,
            'started_received_at' => $when,
            'status' => 'completed',
            'expected_checkpoints' => 2,
            'scanned_checkpoints' => 2,
        ]);

        $scan = PatrolScan::create([
            'uuid' => (string) Str::uuid7(),
            'patrol_id' => $patrol->id,
            'checkpoint_id' => $this->checkpoints[0]->id,
            'occurred_at' => $when,
            'received_at' => $when,
        ]);

        if ($withEvidence) {
            $this->evidenceFor($scan, $when);
        }

        return $shift;
    }

    private function evidenceFor($owner, Carbon $when): Attachment
    {
        $path = 'evidence/'.Str::uuid7().'.jpg';
        Storage::put($path, 'conteudo-da-foto');

        return Attachment::create([
            'uuid' => (string) Str::uuid7(),
            'attachable_type' => $owner->getMorphClass(),
            'attachable_id' => $owner->getKey(),
            'path' => $path,
            'sha256' => hash('sha256', 'conteudo-da-foto'),
            'captured_at' => $when,
            'status' => 'stored',
        ]);
    }

    private function incidentAt(Carbon $when): Incident
    {
        return Incident::create([
            'uuid' => (string) Str::uuid7(),
            'number' => 'RO 001/'.$when->year,
            'sequence' => 1,
            'year' => $when->year,
            'unit_id' => $this->unit->id,
            'incident_type_id' => IncidentType::firstOrCreate(['name' => 'Teste'])->id,
            'occurred_at' => $when,
            'received_at' => $when,
            'description' => 'Ocorrência de teste.',
            'severity' => 'low',
        ]);
    }

    // -------------------------------------------------------------- o que sai

    public function test_old_shifts_go_with_their_patrols_and_scans(): void
    {
        $old = $this->shiftAt(now()->subMonths(14));
        $recent = $this->shiftAt(now()->subMonth());

        $this->purger->run();

        $this->assertDatabaseMissing('shifts', ['id' => $old->id]);
        $this->assertDatabaseHas('shifts', ['id' => $recent->id]);

        // Cascata: rondas, leituras e respostas do turno antigo somem com ele.
        $this->assertSame(1, Patrol::count());
        $this->assertSame(1, PatrolScan::count());
    }

    public function test_evidence_files_are_deleted_from_storage(): void
    {
        $old = $this->shiftAt(now()->subMonths(14));
        $path = Attachment::whereNotNull('path')->first()->path;

        Storage::assertExists($path);

        $this->purger->run();

        Storage::assertMissing($path);
        $this->assertSame(0, Attachment::count(), 'evidência de leitura apagada acompanha a leitura');
    }

    public function test_incidents_survive_the_patrol_purge(): void
    {
        // Prazos diferentes de propósito: a ocorrência é documento, a ronda é
        // dado operacional. Um RO de dois anos tem de continuar legível.
        $shift = $this->shiftAt(now()->subMonths(14));

        $incident = $this->incidentAt(now()->subMonths(14));
        $incident->update(['shift_id' => $shift->id]);

        $this->purger->run();

        $incident->refresh();

        $this->assertDatabaseHas('incidents', ['id' => $incident->id]);
        $this->assertNull($incident->shift_id, 'a referência ao turno apagado vira nula');
        $this->assertSame('Ocorrência de teste.', $incident->description);
    }

    public function test_expired_incidents_are_removed_after_five_years(): void
    {
        $ancient = $this->incidentAt(now()->subYears(6));
        $recent = $this->incidentAt(now()->subYears(2));

        $this->purger->run();

        $this->assertDatabaseMissing('incidents', ['id' => $ancient->id]);
        $this->assertDatabaseHas('incidents', ['id' => $recent->id]);
    }

    public function test_evidence_of_a_retained_incident_loses_the_file_but_keeps_the_row(): void
    {
        // Cenário central da política: ocorrência de dois anos (dentro do prazo)
        // com foto de dois anos (fora do prazo).
        $incident = $this->incidentAt(now()->subYears(2));
        $attachment = $this->evidenceFor($incident, now()->subYears(2));
        $path = $attachment->path;

        $this->purger->run();

        $attachment->refresh();

        Storage::assertMissing($path);
        $this->assertNull($attachment->path);
        $this->assertSame('purged', $attachment->status);
        $this->assertNotNull($attachment->sha256, 'o hash fica: prova o que existiu sem guardar o conteúdo');
        $this->assertDatabaseHas('incidents', ['id' => $incident->id]);
    }

    public function test_technical_logs_have_a_shorter_life(): void
    {
        $old = SyncBatch::create(['device_id' => 'dev-1', 'items_total' => 1]);
        $old->forceFill(['created_at' => now()->subMonths(8)])->save();

        $recent = SyncBatch::create(['device_id' => 'dev-2', 'items_total' => 1]);

        $this->purger->run();

        $this->assertDatabaseMissing('sync_batches', ['id' => $old->id]);
        $this->assertDatabaseHas('sync_batches', ['id' => $recent->id]);
    }

    // ------------------------------------------------------------ o que fica

    public function test_open_shifts_are_never_purged(): void
    {
        // Turno aberto com data antiga é sintoma de problema, não dado vencido:
        // apagar esconderia a falha em vez de resolvê-la.
        $shift = $this->shiftAt(now()->subMonths(14));
        $shift->update(['status' => 'open', 'ended_at' => null]);

        $this->purger->run();

        $this->assertDatabaseHas('shifts', ['id' => $shift->id, 'status' => 'open']);
    }

    public function test_recent_evidence_is_untouched(): void
    {
        $this->shiftAt(now()->subMonths(2));
        $path = Attachment::first()->path;

        $this->purger->run();

        Storage::assertExists($path);
        $this->assertSame('stored', Attachment::first()->status);
    }

    // -------------------------------------------------------------- interação

    public function test_purged_report_stops_reporting_false_late_records(): void
    {
        // Sem a marca data_purged_at, todo RDO antigo acusaria "chegaram
        // registros depois do fechamento" - um falso alarme permanente.
        $date = now()->subMonths(14);
        $this->shiftAt($date, withEvidence: false);

        $builder = app(DailyReportBuilder::class);
        $report = $builder->close(
            $builder->buildOrUpdate($this->unit, $date),
            User::factory()->create(['role' => User::ROLE_ADMIN]),
        );

        $this->assertFalse($builder->hasLateRecords($report));

        $this->purger->run();

        $report->refresh();

        $this->assertNotNull($report->data_purged_at);
        $this->assertFalse($builder->hasLateRecords($report), 'expurgo não pode virar falso alarme');
        $this->assertSame(1, $report->summary['shifts']['total'], 'o conteúdo selado permanece');
    }

    public function test_purged_report_is_not_recalculated_to_zero(): void
    {
        $date = now()->subMonths(14);
        $this->shiftAt($date, withEvidence: false);

        $builder = app(DailyReportBuilder::class);
        $report = $builder->buildOrUpdate($this->unit, $date);

        $this->assertSame(1, $report->summary['shifts']['total']);

        $this->purger->run();

        $again = $builder->buildOrUpdate($this->unit, $date->copy());

        $this->assertSame(1, $again->summary['shifts']['total'], 'rascunho expurgado não zera');
    }

    public function test_orphan_evidence_is_cleaned_up(): void
    {
        $scan = PatrolScan::create([
            'uuid' => (string) Str::uuid7(),
            'patrol_id' => Patrol::create([
                'uuid' => (string) Str::uuid7(),
                'shift_id' => $this->shiftAt(now())->id,
                'patrol_route_id' => $this->route->id,
                'unit_id' => $this->unit->id,
                'started_at' => now(),
                'started_received_at' => now(),
            ])->id,
            'checkpoint_id' => $this->checkpoints[0]->id,
            'occurred_at' => now(),
            'received_at' => now(),
        ]);

        $attachment = $this->evidenceFor($scan, now());

        // Exclusão manual da leitura, fora do fluxo de expurgo.
        $scan->delete();

        $this->purger->run();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }

    // ------------------------------------------------------------- simulação

    public function test_dry_run_counts_without_deleting(): void
    {
        $shift = $this->shiftAt(now()->subMonths(14));

        $run = $this->purger->run(dryRun: true);

        $this->assertTrue($run->dry_run);
        $this->assertSame(1, $run->summary['shifts']);
        $this->assertDatabaseHas('shifts', ['id' => $shift->id]);
        $this->assertSame(1, Attachment::whereNotNull('path')->count());
    }

    public function test_every_run_is_recorded_for_accountability(): void
    {
        // A LGPD exige poder demonstrar a eliminação, não apenas fazê-la.
        $this->shiftAt(now()->subMonths(14));

        $run = $this->purger->run();

        $this->assertInstanceOf(RetentionRun::class, $run);
        $this->assertNotNull($run->finished_at);
        $this->assertNull($run->error);
        $this->assertSame(12, $run->policy['patrol_months']);
        $this->assertGreaterThan(0, $run->totalRemoved());
        $this->assertSame(1, RetentionRun::count());
    }

    public function test_command_runs_and_reports(): void
    {
        $this->shiftAt(now()->subMonths(14));

        $this->artisan('notre-guard:purge-data', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertTrue(RetentionRun::firstOrFail()->dry_run);

        $this->artisan('notre-guard:purge-data')->assertSuccessful();

        $this->assertSame(0, Shift::count());
    }

    public function test_checklist_response_evidence_is_removed_with_the_shift(): void
    {
        $shift = $this->shiftAt(now()->subMonths(14), withEvidence: false);

        $scan = PatrolScan::firstOrFail();
        $template = \App\Models\ChecklistTemplate::create(['unit_id' => $this->unit->id, 'name' => 'T']);
        $item = \App\Models\ChecklistItem::create(['checklist_template_id' => $template->id, 'label' => 'Item']);

        $response = ChecklistResponse::create([
            'uuid' => (string) Str::uuid7(),
            'patrol_scan_id' => $scan->id,
            'checklist_item_id' => $item->id,
            'answer' => 'nonconforming',
        ]);

        $attachment = $this->evidenceFor($response, now()->subMonths(14));
        $path = $attachment->path;

        $this->purger->run();

        Storage::assertMissing($path);
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }
}
