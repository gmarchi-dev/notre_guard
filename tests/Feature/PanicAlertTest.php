<?php

namespace Tests\Feature;

use App\Models\SafetyAlert;
use App\Models\User;
use App\Notifications\SafetyAlertRaised;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\Feature\Api\SyncTestCase;

/**
 * Botão de pânico. É o caminho onde uma falha custa mais caro do que em
 * qualquer outro lugar do sistema.
 */
class PanicAlertTest extends SyncTestCase
{
    private function panic(array $overrides = [], ?string $token = null): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer '.($token ?? $this->login()),
            'X-Device-Id' => $this->deviceId,
        ])->postJson(route('api.panic'), array_merge([
            'uuid' => (string) Str::uuid7(),
            'occurred_at' => now()->toIso8601String(),
            'latitude' => -22.9056,
            'longitude' => -47.0608,
            'accuracy_m' => 12,
        ], $overrides));
    }

    public function test_panic_is_recorded_immediately(): void
    {
        Notification::fake();

        $response = $this->panic();

        $response->assertCreated()->assertJsonPath('status', 'received');

        $alert = SafetyAlert::firstOrFail();

        $this->assertSame(SafetyAlert::KIND_PANIC, $alert->kind);
        $this->assertSame($this->guard->id, $alert->security_guard_id);
        $this->assertSame('open', $alert->status);
        $this->assertEquals(-22.9056, (float) $alert->latitude);
    }

    public function test_supervision_is_notified_without_waiting_for_a_queue_worker(): void
    {
        // O ponto central do desenho: a notificação de pânico NÃO é
        // ShouldQueue. Se dependesse do worker, um worker parado significaria
        // pedido de socorro silencioso.
        Notification::fake();

        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

        $this->panic()->assertCreated();

        Notification::assertSentTo($supervisor, SafetyAlertRaised::class);

        $this->assertFalse(
            (new SafetyAlertRaised(SafetyAlert::firstOrFail())) instanceof \Illuminate\Contracts\Queue\ShouldQueue,
            'a notificação de pânico não pode ser enfileirada',
        );
    }

    public function test_panic_without_gps_is_still_accepted(): void
    {
        // Alerta sem localização chega e serve; alerta que não chega não serve.
        Notification::fake();

        $this->panic(['latitude' => null, 'longitude' => null, 'accuracy_m' => null])
            ->assertCreated();

        $this->assertNull(SafetyAlert::firstOrFail()->latitude);
    }

    public function test_panic_links_the_open_shift_and_patrol(): void
    {
        Notification::fake();

        $shiftUuid = (string) Str::uuid7();
        $patrolUuid = (string) Str::uuid7();

        $token = $this->login();

        $this->sync([
            $this->event('shift.start', ['post_id' => $this->post->id], uuid: $shiftUuid),
            $this->event('patrol.start', ['shift_uuid' => $shiftUuid, 'patrol_route_id' => $this->route->id], uuid: $patrolUuid),
        ], $token)->assertOk();

        $this->panic([], $token)->assertCreated();

        $alert = SafetyAlert::firstOrFail();

        $this->assertNotNull($alert->shift_id, 'o alerta precisa apontar para o turno em curso');
        $this->assertNotNull($alert->patrol_id);
        $this->assertSame($this->unit->id, $alert->unit_id);
    }

    public function test_pressing_twice_does_not_create_two_alerts(): void
    {
        // Nervosismo aperta duas vezes. O uuid é do aparelho, então o reenvio
        // do mesmo acionamento é reconhecido.
        Notification::fake();

        $uuid = (string) Str::uuid7();
        $token = $this->login();

        $this->panic(['uuid' => $uuid], $token)->assertCreated();
        $this->panic(['uuid' => $uuid], $token)->assertOk()->assertJsonPath('status', 'duplicate');

        $this->assertSame(1, SafetyAlert::count());
        Notification::assertSentToTimes(User::factory()->create(['role' => User::ROLE_ADMIN]), SafetyAlertRaised::class, 0);
    }

    public function test_offline_panic_arrives_through_the_queue_with_the_original_time(): void
    {
        // Aparelho sem rede no acionamento: o app enfileira o mesmo evento e ele
        // chega depois, com a hora em que o vigilante apertou o botão.
        Notification::fake();

        $pressedAt = now()->subMinutes(20);

        $this->sync([
            $this->event('panic.alert', ['latitude' => -22.9056, 'longitude' => -47.0608], $pressedAt->toIso8601String()),
        ])->assertOk();

        $alert = SafetyAlert::firstOrFail();

        $this->assertSame(SafetyAlert::KIND_PANIC, $alert->kind);
        $this->assertSame($pressedAt->format('Y-m-d H:i'), $alert->occurred_at->format('Y-m-d H:i'));
        $this->assertTrue($alert->received_at->gt($alert->occurred_at), 'a demora do envio fica visível');
    }

    public function test_direct_delivery_then_queued_fallback_does_not_duplicate(): void
    {
        // Cenário real: a entrega direta funciona, mas o app não recebe a
        // resposta e enfileira por precaução. O mesmo uuid impede o segundo
        // alerta — é por isso que o app reaproveita o uuid no fallback.
        Notification::fake();

        $uuid = (string) Str::uuid7();
        $token = $this->login();

        $this->panic(['uuid' => $uuid], $token)->assertCreated();

        $response = $this->sync([
            $this->event('panic.alert', [], null, $uuid),
        ], $token);

        $response->assertOk();
        $this->assertSame('duplicate', $response->json('results.0.status'));
        $this->assertSame(1, SafetyAlert::count());
    }

    private function panicStatus(string $uuid, ?string $token = null): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer '.($token ?? $this->login()),
            'X-Device-Id' => $this->deviceId,
        ])->getJson(route('api.panic.show', ['uuid' => $uuid]));
    }

    public function test_device_learns_when_a_human_acknowledged(): void
    {
        // A informação que faltava no aparelho: "o servidor gravou" não é
        // "alguém está indo". Quem apertou o botão espera sozinho até saber.
        Notification::fake();

        $uuid = (string) Str::uuid7();
        $token = $this->login();

        $this->panic(['uuid' => $uuid], $token)->assertCreated();

        $this->panicStatus($uuid, $token)
            ->assertOk()
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('acknowledged_at', null);

        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR, 'name' => 'Ana Supervisão']);

        SafetyAlert::firstOrFail()->update([
            'status' => 'acknowledged',
            'acknowledged_by_user_id' => $supervisor->id,
            'acknowledged_at' => now(),
        ]);

        $response = $this->panicStatus($uuid, $token)->assertOk();

        $this->assertSame('acknowledged', $response->json('status'));
        $this->assertSame('Ana Supervisão', $response->json('acknowledged_by'));
        $this->assertNotNull($response->json('acknowledged_at'));
    }

    public function test_a_guard_cannot_read_someone_elses_alert(): void
    {
        Notification::fake();

        $uuid = (string) Str::uuid7();

        $this->panic(['uuid' => $uuid])->assertCreated();

        $other = \App\Models\SecurityGuard::create([
            'user_id' => User::factory()->create(['role' => User::ROLE_GUARD])->id,
            'registration' => 'VIG-999',
            'default_unit_id' => $this->unit->id,
        ]);

        SafetyAlert::firstOrFail()->update(['security_guard_id' => $other->id]);

        $this->panicStatus($uuid)->assertNotFound();
    }

    public function test_status_of_an_alert_still_in_the_queue_is_not_found(): void
    {
        // Acionamento offline: o uuid existe no aparelho, mas ainda não no
        // servidor. O app trata isso como "continua tentando", não como erro.
        $this->panicStatus((string) Str::uuid7())->assertNotFound();
    }

    public function test_panic_requires_authentication(): void
    {
        $this->postJson(route('api.panic'), ['uuid' => (string) Str::uuid7(), 'occurred_at' => now()->toIso8601String()])
            ->assertUnauthorized();

        $this->assertSame(0, SafetyAlert::count());
    }

    public function test_alert_survives_a_notification_failure(): void
    {
        // Se o e-mail falhar, o acionamento não pode ser perdido: ele ainda
        // aparece na tela de alertas.
        Notification::fake();
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('SMTP fora'));

        $this->panic()->assertCreated();

        $this->assertSame(1, SafetyAlert::count());
        $this->assertSame('open', SafetyAlert::firstOrFail()->status);
    }
}
