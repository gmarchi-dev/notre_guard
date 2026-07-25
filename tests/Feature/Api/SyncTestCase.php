<?php

namespace Tests\Feature\Api;

use App\Models\Checkpoint;
use App\Models\PatrolRoute;
use App\Models\Post;
use App\Models\SecurityGuard;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

abstract class SyncTestCase extends TestCase
{
    use RefreshDatabase;

    protected Unit $unit;

    protected Post $post;

    protected PatrolRoute $route;

    /** @var list<Checkpoint> */
    protected array $checkpoints;

    protected SecurityGuard $guard;

    protected string $deviceId = 'device-test-0001';

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::create([
            'name' => 'Unidade Teste',
            'code' => 'TST',
            'latitude' => -22.9056,
            'longitude' => -47.0608,
        ]);

        $this->post = Post::create([
            'unit_id' => $this->unit->id,
            'name' => 'Portaria',
            'kind' => 'reception',
        ]);

        $this->checkpoints = [];

        foreach ([['PC-01', -22.90560, -47.06080], ['PC-02', -22.90600, -47.06120]] as [$code, $lat, $lng]) {
            $this->checkpoints[] = Checkpoint::create([
                'unit_id' => $this->unit->id,
                'code' => $code,
                'name' => "Ponto {$code}",
                'latitude' => $lat,
                'longitude' => $lng,
                'radius_m' => 50,
            ]);
        }

        $this->route = PatrolRoute::create([
            'unit_id' => $this->unit->id,
            'name' => 'Perimetral',
            'ordered' => true,
        ]);

        foreach ($this->checkpoints as $i => $checkpoint) {
            $this->route->checkpoints()->attach($checkpoint->id, [
                'position' => $i + 1,
                'required' => true,
            ]);
        }

        $user = User::factory()->create([
            'role' => User::ROLE_GUARD,
            'password' => Hash::make('segredo123'),
        ]);

        $this->guard = SecurityGuard::create([
            'user_id' => $user->id,
            'default_unit_id' => $this->unit->id,
            'registration' => 'VIG-001',
        ]);
    }

    protected function login(): string
    {
        return $this->postJson(route('api.auth.login'), [
            'registration' => 'VIG-001',
            'password' => 'segredo123',
            'device_id' => $this->deviceId,
            'device_name' => 'Aparelho de teste',
        ])->json('token');
    }

    /**
     * @param  list<array<string, mixed>>  $events
     */
    protected function sync(array $events, ?string $token = null, ?string $clientSentAt = null): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer '.($token ?? $this->login()),
            'X-Device-Id' => $this->deviceId,
        ])->postJson(route('api.sync.events'), array_filter([
            'client_sent_at' => $clientSentAt,
            'events' => $events,
        ]));
    }

    protected function event(string $type, array $payload = [], ?string $occurredAt = null, ?string $uuid = null): array
    {
        return [
            'uuid' => $uuid ?? (string) Str::uuid7(),
            'type' => $type,
            'occurred_at' => $occurredAt ?? now()->toIso8601String(),
            'payload' => $payload,
        ];
    }
}
