<?php

namespace Tests\Feature;

use App\Filament\Resources\Checkpoints\CheckpointResource;
use App\Filament\Resources\ChecklistTemplates\ChecklistTemplateResource;
use App\Filament\Resources\Incidents\IncidentResource;
use App\Filament\Resources\SecurityGuards\SecurityGuardResource;
use App\Filament\Resources\Units\UnitResource;
use App\Models\Checkpoint;
use App\Models\ChecklistTemplate;
use App\Models\IncidentType;
use App\Models\SecurityGuard;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Isolamento entre unidades. É o que permite dar acesso a um gestor de unidade
 * sem expor a operação de todo o colégio.
 */
class UnitScopeTest extends TestCase
{
    use RefreshDatabase;

    private Unit $unitA;

    private Unit $unitB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unitA = Unit::create(['name' => 'Unidade A', 'code' => 'UA']);
        $this->unitB = Unit::create(['name' => 'Unidade B', 'code' => 'UB']);

        foreach ([$this->unitA, $this->unitB] as $unit) {
            Checkpoint::create([
                'unit_id' => $unit->id,
                'code' => 'PC-01',
                'name' => "Ponto da {$unit->code}",
            ]);

            ChecklistTemplate::create(['unit_id' => $unit->id, 'name' => "Checklist {$unit->code}"]);

            $user = User::factory()->create(['role' => User::ROLE_GUARD]);

            SecurityGuard::create([
                'user_id' => $user->id,
                'default_unit_id' => $unit->id,
                'registration' => "VIG-{$unit->code}",
            ]);
        }

        ChecklistTemplate::create(['unit_id' => null, 'name' => 'Checklist global']);
    }

    private function managerOf(Unit $unit): User
    {
        return User::factory()->create([
            'role' => User::ROLE_UNIT_MANAGER,
            'unit_id' => $unit->id,
        ]);
    }

    public function test_unit_manager_sees_only_its_own_unit(): void
    {
        $this->actingAs($this->managerOf($this->unitA));

        $this->assertSame(['Unidade A'], UnitResource::getEloquentQuery()->pluck('name')->all());
        $this->assertSame(['Ponto da UA'], CheckpointResource::getEloquentQuery()->pluck('name')->all());
        $this->assertSame(['VIG-UA'], SecurityGuardResource::getEloquentQuery()->pluck('registration')->all());
    }

    public function test_admin_and_supervision_see_every_unit(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_SUPERVISOR] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));

            $this->assertCount(2, UnitResource::getEloquentQuery()->get(), "perfil {$role}");
            $this->assertCount(2, CheckpointResource::getEloquentQuery()->get(), "perfil {$role}");
        }
    }

    public function test_global_checklist_stays_visible_to_the_unit_manager(): void
    {
        // Checklist sem unidade é modelo institucional: vale para todo mundo.
        $this->actingAs($this->managerOf($this->unitB));

        $names = ChecklistTemplateResource::getEloquentQuery()->pluck('name')->sort()->values()->all();

        $this->assertSame(['Checklist UB', 'Checklist global'], $names);
    }

    public function test_manager_without_unit_is_not_scoped_by_accident(): void
    {
        // Gestor sem unidade definida não deve virar um usuário cego nem um
        // usuário com acesso total silencioso — hoje o comportamento é ver tudo,
        // então o cadastro precisa exigir a unidade.
        $manager = User::factory()->create(['role' => User::ROLE_UNIT_MANAGER, 'unit_id' => null]);

        $this->assertFalse($manager->isScopedToUnit());
    }

    public function test_incidents_are_scoped_by_unit(): void
    {
        $type = IncidentType::create(['name' => 'Teste']);

        foreach ([$this->unitA, $this->unitB] as $unit) {
            $unit->incidents()->create([
                'uuid' => (string) \Illuminate\Support\Str::uuid7(),
                'number' => 'RO 001/2026',
                'sequence' => 1,
                'year' => 2026,
                'incident_type_id' => $type->id,
                'occurred_at' => now(),
                'received_at' => now(),
                'description' => "Ocorrência da {$unit->code}",
            ]);
        }

        $this->actingAs($this->managerOf($this->unitA));

        $this->assertSame(
            ['Ocorrência da UA'],
            IncidentResource::getEloquentQuery()->pluck('description')->all(),
        );
    }
}
