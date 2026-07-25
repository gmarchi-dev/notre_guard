<?php

namespace Tests\Feature;

use App\Models\Checkpoint;
use App\Models\IncidentType;
use App\Models\PatrolRoute;
use App\Models\Post;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_checkpoints_and_posts_all_have_tokens(): void
    {
        // Regressão: DatabaseSeeder não pode usar WithoutModelEvents, senão o
        // evento que gera o qr_token não roda e os pontos nascem sem QR Code.
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, Checkpoint::count());
        $this->assertSame(0, Checkpoint::whereNull('qr_token')->count());
        $this->assertSame(0, Post::whereNull('qr_token')->count());
    }

    public function test_demo_route_has_ordered_checkpoints(): void
    {
        $this->seed(DatabaseSeeder::class);

        $route = PatrolRoute::firstOrFail();
        $positions = $route->checkpoints->pluck('pivot.position')->all();

        $this->assertSame(range(1, count($positions)), $positions);
        $this->assertSame(count($positions), $route->requiredCheckpointCount());
    }

    public function test_incident_taxonomy_is_hierarchical(): void
    {
        $this->seed(DatabaseSeeder::class);

        $parents = IncidentType::whereNull('parent_id')->get();

        $this->assertGreaterThan(0, $parents->count());

        foreach ($parents as $parent) {
            $this->assertGreaterThan(0, $parent->children()->count(), "{$parent->name} sem subtipos");
        }
    }
}
