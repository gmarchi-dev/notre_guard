<?php

namespace Tests\Feature;

use App\Models\IncidentType;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\IncidentReported;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\Feature\Api\SyncTestCase;

/**
 * Aviso de ocorrência grave. O que se testa aqui é quem recebe e quando —
 * errar isso significa ou silêncio numa emergência, ou ruído que faz a
 * supervisão ignorar o sistema.
 */
class IncidentNotificationTest extends SyncTestCase
{
    private function reportIncident(string $severity, ?IncidentType $type = null): void
    {
        $shiftUuid = (string) Str::uuid7();

        $this->sync([
            $this->event('shift.start', ['post_id' => $this->post->id], uuid: $shiftUuid),
            $this->event('incident.report', [
                'shift_uuid' => $shiftUuid,
                'incident_type_id' => ($type ?? IncidentType::create(['name' => 'Ocorrência de teste']))->id,
                'description' => 'Relato de teste.',
                'severity' => $severity,
            ]),
        ])->assertOk();
    }

    public function test_high_severity_notifies_supervision_and_admin(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

        $this->reportIncident('high');

        Notification::assertSentTo([$admin, $supervisor], IncidentReported::class);
    }

    public function test_low_severity_stays_silent(): void
    {
        // Ocorrência rotineira entra no RDO; não acorda ninguém.
        Notification::fake();

        User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

        $this->reportIncident('low');

        Notification::assertNothingSent();
    }

    public function test_incident_type_can_force_the_alert_regardless_of_severity(): void
    {
        // É como a gestão marca um assunto sensível sem depender da gravidade
        // que o vigilante escolheu em campo.
        Notification::fake();

        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

        $type = IncidentType::create([
            'name' => 'Câmera inoperante',
            'notify_supervision' => true,
        ]);

        $this->reportIncident('low', $type);

        Notification::assertSentTo($supervisor, IncidentReported::class);
    }

    public function test_unit_manager_only_hears_about_its_own_unit(): void
    {
        Notification::fake();

        $otherUnit = Unit::create(['name' => 'Outra', 'code' => 'OUT']);

        $ownManager = User::factory()->create([
            'role' => User::ROLE_UNIT_MANAGER,
            'unit_id' => $this->unit->id,
        ]);

        $otherManager = User::factory()->create([
            'role' => User::ROLE_UNIT_MANAGER,
            'unit_id' => $otherUnit->id,
        ]);

        $this->reportIncident('critical');

        Notification::assertSentTo($ownManager, IncidentReported::class);
        Notification::assertNotSentTo($otherManager, IncidentReported::class);
    }

    public function test_guards_and_inactive_users_are_never_notified(): void
    {
        Notification::fake();

        $guardUser = $this->guard->user;
        $inactive = User::factory()->create(['role' => User::ROLE_SUPERVISOR, 'active' => false]);

        $this->reportIncident('critical');

        Notification::assertNotSentTo($guardUser, IncidentReported::class);
        Notification::assertNotSentTo($inactive, IncidentReported::class);
    }

    public function test_resending_the_same_event_does_not_notify_twice(): void
    {
        // A fila do aparelho reenvia até o servidor confirmar. O aviso não pode
        // sair de novo a cada tentativa.
        Notification::fake();

        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

        $shiftUuid = (string) Str::uuid7();
        $incidentUuid = (string) Str::uuid7();

        $events = [
            $this->event('shift.start', ['post_id' => $this->post->id], uuid: $shiftUuid),
            $this->event('incident.report', [
                'shift_uuid' => $shiftUuid,
                'incident_type_id' => IncidentType::create(['name' => 'Furto'])->id,
                'description' => 'Tentativa de furto no estacionamento.',
                'severity' => 'critical',
            ], uuid: $incidentUuid),
        ];

        $token = $this->login();
        $this->sync($events, $token)->assertOk();
        $this->sync($events, $token)->assertOk();

        Notification::assertSentToTimes($supervisor, IncidentReported::class, 1);
    }

    public function test_notification_carries_the_incident_number_and_link(): void
    {
        Notification::fake();

        $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);

        $this->reportIncident('critical');

        Notification::assertSentTo(
            $supervisor,
            IncidentReported::class,
            function (IncidentReported $notification) use ($supervisor) {
                $mail = $notification->toMail($supervisor);
                $number = $notification->incident->number;

                $this->assertStringContainsString($number, $mail->subject);
                $this->assertStringContainsString('Crítica', $mail->subject);
                $this->assertNotEmpty($notification->toDatabase($supervisor));

                return true;
            },
        );
    }
}
