<?php

namespace Tests\Feature\Api;

use App\Models\PatrolScan;
use Illuminate\Support\Str;

class DeviationTest extends SyncTestCase
{
    /**
     * @return array{0: string, 1: string} uuids do turno e da ronda
     */
    private function openPatrol(): array
    {
        $shiftUuid = (string) Str::uuid7();
        $patrolUuid = (string) Str::uuid7();

        $this->sync([
            $this->event('shift.start', ['post_id' => $this->post->id], uuid: $shiftUuid),
            $this->event('patrol.start', ['shift_uuid' => $shiftUuid, 'patrol_route_id' => $this->route->id], uuid: $patrolUuid),
        ])->assertOk();

        return [$shiftUuid, $patrolUuid];
    }

    public function test_reading_far_from_the_checkpoint_is_accepted_and_flagged(): void
    {
        // A regra de ouro: o app nunca recusa. Fora do raio vira desvio para o
        // supervisor analisar, não erro na cara do vigilante.
        [, $patrolUuid] = $this->openPatrol();

        $response = $this->sync([
            $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[0]->id,
                'latitude' => -22.9200,   // ~1,6 km do ponto
                'longitude' => -47.0700,
            ]),
        ]);

        $this->assertSame('accepted', $response->json('results.0.status'));

        $scan = PatrolScan::firstOrFail();
        $this->assertContains(PatrolScan::DEVIATION_OUT_OF_RADIUS, $scan->deviations);
        $this->assertGreaterThan($this->checkpoints[0]->radius_m, $scan->distance_m);
    }

    public function test_reading_without_gps_is_accepted_and_flagged(): void
    {
        [, $patrolUuid] = $this->openPatrol();

        $this->sync([
            $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[0]->id,
            ]),
        ])->assertOk();

        $scan = PatrolScan::firstOrFail();
        $this->assertContains(PatrolScan::DEVIATION_NO_GPS, $scan->deviations);
        $this->assertNull($scan->distance_m);
    }

    public function test_reading_inside_the_radius_has_no_deviation(): void
    {
        [, $patrolUuid] = $this->openPatrol();

        $this->sync([
            $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[0]->id,
                'latitude' => -22.90562,
                'longitude' => -47.06082,
            ]),
        ])->assertOk();

        $scan = PatrolScan::firstOrFail();
        $this->assertNull($scan->deviations);
        $this->assertLessThan(50, $scan->distance_m);
    }

    public function test_out_of_order_reading_is_flagged_on_ordered_routes(): void
    {
        [, $patrolUuid] = $this->openPatrol();

        $this->sync([
            $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[1]->id, // posição 2 primeiro
                'latitude' => -22.90600,
                'longitude' => -47.06120,
            ]),
            $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[0]->id, // volta para a posição 1
                'latitude' => -22.90560,
                'longitude' => -47.06080,
            ]),
        ])->assertOk();

        $scans = PatrolScan::orderBy('id')->get();

        $this->assertNull($scans[0]->deviations, 'a primeira leitura não tem com o que desalinhar');
        $this->assertContains(PatrolScan::DEVIATION_OUT_OF_ORDER, $scans[1]->deviations);
    }

    public function test_skewed_device_clock_is_flagged_across_the_batch(): void
    {
        [, $patrolUuid] = $this->openPatrol();

        $this->sync(
            [
                $this->event('patrol.scan', [
                    'patrol_uuid' => $patrolUuid,
                    'checkpoint_id' => $this->checkpoints[0]->id,
                    'latitude' => -22.90560,
                    'longitude' => -47.06080,
                ]),
            ],
            clientSentAt: now()->subHours(3)->toIso8601String(),
        )->assertOk();

        $this->assertContains(
            PatrolScan::DEVIATION_CLOCK_SKEW,
            PatrolScan::firstOrFail()->deviations,
        );
    }

    public function test_reading_outside_the_scheduled_window_is_flagged(): void
    {
        // Janela que não cobre o horário do teste, em todos os dias da semana.
        $this->route->schedules()->create([
            'label' => 'Ronda noturna',
            'window_start' => now()->addHours(6)->format('H:i:s'),
            'window_end' => now()->addHours(7)->format('H:i:s'),
            'weekdays' => [0, 1, 2, 3, 4, 5, 6],
        ]);

        [, $patrolUuid] = $this->openPatrol();

        $this->sync([
            $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[0]->id,
                'latitude' => -22.90560,
                'longitude' => -47.06080,
            ]),
        ])->assertOk();

        $this->assertContains(
            PatrolScan::DEVIATION_OUT_OF_WINDOW,
            PatrolScan::firstOrFail()->deviations,
        );
    }
}
