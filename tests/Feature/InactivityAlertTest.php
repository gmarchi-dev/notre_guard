<?php

namespace Tests\Feature;

use App\Models\Patrol;
use App\Models\SafetyAlert;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\SafetyAlertRaised;
use App\Services\InactivityWatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\Feature\Api\SyncTestCase;

/**
 * Alerta de inatividade em ronda.
 *
 * O equilíbrio aqui é entre ruído e atraso: alertar demais faz a supervisão
 * parar de olhar, alertar de menos atrasa o socorro.
 */
class InactivityAlertTest extends SyncTestCase
{
    private InactivityWatcher $watcher;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config(['safety.inactivity_minutes' => 30]);

        $this->watcher = app(InactivityWatcher::class);
    }

    private function patrolStartedAt(Carbon $when, string $status = 'in_progress'): Patrol
    {
        $shift = Shift::create([
            'uuid' => (string) Str::uuid7(),
            'security_guard_id' => $this->guard->id,
            'post_id' => $this->post->id,
            'unit_id' => $this->unit->id,
            'started_at' => $when,
            'started_received_at' => $when,
            'status' => 'open',
        ]);

        return Patrol::create([
            'uuid' => (string) Str::uuid7(),
            'shift_id' => $shift->id,
            'patrol_route_id' => $this->route->id,
            'unit_id' => $this->unit->id,
            'started_at' => $when,
            'started_received_at' => $when,
            'status' => $status,
            'expected_checkpoints' => 2,
        ]);
    }

    public function test_silent_patrol_raises_an_alert(): void
    {
        $this->patrolStartedAt(now()->subMinutes(45));

        $this->assertSame(1, $this->watcher->sweep());

        $alert = SafetyAlert::firstOrFail();

        $this->assertSame(SafetyAlert::KIND_INACTIVITY, $alert->kind);
        $this->assertGreaterThanOrEqual(45, $alert->silence_minutes);
        $this->assertSame($this->guard->id, $alert->security_guard_id);
    }

    public function test_patrol_within_the_threshold_is_left_alone(): void
    {
        $this->patrolStartedAt(now()->subMinutes(10));

        $this->assertSame(0, $this->watcher->sweep());
        $this->assertSame(0, SafetyAlert::count());
    }

    public function test_recent_scan_resets_the_silence(): void
    {
        $patrol = $this->patrolStartedAt(now()->subHours(3));

        $patrol->scans()->create([
            'uuid' => (string) Str::uuid7(),
            'checkpoint_id' => $this->checkpoints[0]->id,
            'occurred_at' => now()->subMinutes(5),
            'received_at' => now()->subMinutes(5),
        ]);

        $this->assertSame(0, $this->watcher->sweep(), 'ronda longa com leitura recente não é inatividade');
    }

    public function test_finished_patrol_is_never_flagged(): void
    {
        $this->patrolStartedAt(now()->subHours(5), status: 'completed');

        $this->assertSame(0, $this->watcher->sweep());
    }

    public function test_a_guard_standing_at_a_post_without_patrol_is_not_flagged(): void
    {
        // Portaria pode passar horas sem ler ponto, e isso é normal. Alertar
        // aqui geraria ruído constante e mataria a credibilidade do alerta.
        Shift::create([
            'uuid' => (string) Str::uuid7(),
            'security_guard_id' => $this->guard->id,
            'post_id' => $this->post->id,
            'unit_id' => $this->unit->id,
            'started_at' => now()->subHours(6),
            'started_received_at' => now()->subHours(6),
            'status' => 'open',
        ]);

        $this->assertSame(0, $this->watcher->sweep());
    }

    public function test_the_same_patrol_is_alerted_only_once(): void
    {
        // O agendador roda a cada 5 minutos. Sem isto, uma ronda travada geraria
        // um alerta novo a cada passagem até alguém intervir.
        $this->patrolStartedAt(now()->subMinutes(45));

        $this->assertSame(1, $this->watcher->sweep());
        $this->assertSame(0, $this->watcher->sweep());
        $this->assertSame(0, $this->watcher->sweep());

        $this->assertSame(1, SafetyAlert::count());
    }

    public function test_alert_carries_the_last_known_position(): void
    {
        // Não é onde ele está: é onde foi visto por último, que é o que orienta
        // a busca.
        $patrol = $this->patrolStartedAt(now()->subHours(2));

        $patrol->scans()->create([
            'uuid' => (string) Str::uuid7(),
            'checkpoint_id' => $this->checkpoints[0]->id,
            'occurred_at' => now()->subMinutes(50),
            'received_at' => now()->subMinutes(50),
            'latitude' => -22.90561,
            'longitude' => -47.06081,
        ]);

        $this->watcher->sweep();

        $alert = SafetyAlert::firstOrFail();

        $this->assertEquals(-22.90561, (float) $alert->latitude);
        $this->assertGreaterThanOrEqual(50, $alert->silence_minutes);
    }

    public function test_supervision_is_notified(): void
    {
        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

        $this->patrolStartedAt(now()->subMinutes(45));
        $this->watcher->sweep();

        Notification::assertSentTo($supervisor, SafetyAlertRaised::class);
    }

    public function test_silence_uses_device_time_not_server_arrival(): void
    {
        // Leitura feita há 5 minutos que só chegou agora não é inatividade - o
        // vigilante agiu, a rede é que estava fora.
        $patrol = $this->patrolStartedAt(now()->subHours(2));

        $patrol->scans()->create([
            'uuid' => (string) Str::uuid7(),
            'checkpoint_id' => $this->checkpoints[0]->id,
            'occurred_at' => now()->subMinutes(5),
            'received_at' => now(),
        ]);

        $this->assertLessThan(30, $this->watcher->silenceMinutes($patrol));
        $this->assertSame(0, $this->watcher->sweep());
    }

    public function test_command_runs(): void
    {
        $this->patrolStartedAt(now()->subMinutes(45));

        $this->artisan('notre-guard:watch-inactivity')->assertSuccessful();

        $this->assertSame(1, SafetyAlert::count());
    }
}
