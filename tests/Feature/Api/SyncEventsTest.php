<?php

namespace Tests\Feature\Api;

use App\Models\Patrol;
use App\Models\PatrolScan;
use App\Models\Shift;
use Illuminate\Support\Str;

class SyncEventsTest extends SyncTestCase
{
    public function test_full_shift_flows_through_in_one_batch(): void
    {
        $shiftUuid = (string) Str::uuid7();
        $patrolUuid = (string) Str::uuid7();

        $response = $this->sync([
            $this->event('shift.start', ['post_id' => $this->post->id, 'latitude' => -22.9056, 'longitude' => -47.0608], uuid: $shiftUuid),
            $this->event('patrol.start', ['shift_uuid' => $shiftUuid, 'patrol_route_id' => $this->route->id], uuid: $patrolUuid),
            $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[0]->id,
                'latitude' => -22.90560,
                'longitude' => -47.06080,
            ]),
            $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[1]->id,
                'latitude' => -22.90600,
                'longitude' => -47.06120,
            ]),
            $this->event('patrol.end', ['patrol_uuid' => $patrolUuid]),
            $this->event('shift.end', ['shift_uuid' => $shiftUuid, 'handover_notes' => 'Sem pendências.']),
        ]);

        $response->assertOk();

        $this->assertSame(
            ['accepted', 'accepted', 'accepted', 'accepted', 'accepted', 'accepted'],
            array_column($response->json('results'), 'status'),
        );

        $patrol = Patrol::where('uuid', $patrolUuid)->firstOrFail();
        $this->assertSame(2, $patrol->scanned_checkpoints);
        $this->assertSame(2, $patrol->expected_checkpoints);
        $this->assertSame('completed', $patrol->status);
        $this->assertNull($patrol->deviations);

        $shift = Shift::where('uuid', $shiftUuid)->firstOrFail();
        $this->assertSame('closed', $shift->status);
        $this->assertNotNull($shift->chain_hash, 'o fechamento deve selar a cadeia de integridade');
    }

    public function test_resending_the_same_batch_never_duplicates(): void
    {
        $shiftUuid = (string) Str::uuid7();
        $patrolUuid = (string) Str::uuid7();
        $scanUuid = (string) Str::uuid7();

        $events = [
            $this->event('shift.start', ['post_id' => $this->post->id], uuid: $shiftUuid),
            $this->event('patrol.start', ['shift_uuid' => $shiftUuid, 'patrol_route_id' => $this->route->id], uuid: $patrolUuid),
            $this->event('patrol.scan', ['patrol_uuid' => $patrolUuid, 'checkpoint_id' => $this->checkpoints[0]->id], uuid: $scanUuid),
        ];

        $token = $this->login();

        $this->sync($events, $token)->assertOk();
        $second = $this->sync($events, $token)->assertOk();

        $this->assertSame(
            ['duplicate', 'duplicate', 'duplicate'],
            array_column($second->json('results'), 'status'),
        );

        $this->assertSame(1, Shift::count());
        $this->assertSame(1, Patrol::count());
        $this->assertSame(1, PatrolScan::count());

        // O contador da ronda não pode ser incrementado de novo pelo reenvio.
        $this->assertSame(1, Patrol::firstOrFail()->scanned_checkpoints);
    }

    public function test_event_arriving_before_its_parent_is_retryable(): void
    {
        // Cenário real: a fila do aparelho subiu fora de ordem porque a rede caiu
        // no meio do lote. O registro não pode ser descartado.
        $response = $this->sync([
            $this->event('patrol.scan', [
                'patrol_uuid' => (string) Str::uuid7(),
                'checkpoint_id' => $this->checkpoints[0]->id,
            ]),
        ]);

        $response->assertOk();
        $result = $response->json('results.0');

        $this->assertSame('failed', $result['status']);
        $this->assertTrue($result['retryable']);
        $this->assertSame('parent_missing', $result['code']);
    }

    public function test_one_bad_event_does_not_sink_the_batch(): void
    {
        $shiftUuid = (string) Str::uuid7();

        $response = $this->sync([
            $this->event('shift.start', ['post_id' => $this->post->id], uuid: $shiftUuid),
            $this->event('patrol.start', ['shift_uuid' => $shiftUuid, 'patrol_route_id' => 99999]),
            $this->event('shift.end', ['shift_uuid' => $shiftUuid]),
        ]);

        $response->assertOk();

        $statuses = array_column($response->json('results'), 'status');
        $this->assertSame(['accepted', 'failed', 'accepted'], $statuses);
        $this->assertFalse($response->json('results.1.retryable'));
        $this->assertSame('closed', Shift::firstOrFail()->status);
    }

    public function test_skipped_checkpoint_requires_justification(): void
    {
        $shiftUuid = (string) Str::uuid7();
        $patrolUuid = (string) Str::uuid7();

        $response = $this->sync([
            $this->event('shift.start', ['post_id' => $this->post->id], uuid: $shiftUuid),
            $this->event('patrol.start', ['shift_uuid' => $shiftUuid, 'patrol_route_id' => $this->route->id], uuid: $patrolUuid),
            $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[0]->id,
                'outcome' => 'skipped',
            ]),
            $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[1]->id,
                'outcome' => 'skipped',
                'justification' => 'Portão interditado por obra.',
            ]),
        ]);

        $this->assertSame('failed', $response->json('results.2.status'));
        $this->assertSame('justification_required', $response->json('results.2.code'));
        $this->assertSame('accepted', $response->json('results.3.status'));

        $scan = PatrolScan::where('outcome', 'skipped')->firstOrFail();
        $this->assertContains(PatrolScan::DEVIATION_SKIPPED, $scan->deviations);

        // Ponto pulado não conta como realizado.
        $this->assertSame(0, Patrol::firstOrFail()->scanned_checkpoints);
    }

    public function test_incomplete_patrol_is_flagged_on_close(): void
    {
        $shiftUuid = (string) Str::uuid7();
        $patrolUuid = (string) Str::uuid7();

        $this->sync([
            $this->event('shift.start', ['post_id' => $this->post->id], uuid: $shiftUuid),
            $this->event('patrol.start', ['shift_uuid' => $shiftUuid, 'patrol_route_id' => $this->route->id], uuid: $patrolUuid),
            $this->event('patrol.scan', ['patrol_uuid' => $patrolUuid, 'checkpoint_id' => $this->checkpoints[0]->id]),
            $this->event('patrol.end', ['patrol_uuid' => $patrolUuid]),
        ])->assertOk();

        $this->assertSame(['incomplete'], Patrol::firstOrFail()->deviations);
    }

    public function test_unauthenticated_device_is_rejected(): void
    {
        $this->postJson(route('api.sync.events'), ['events' => []])->assertUnauthorized();
    }
}
