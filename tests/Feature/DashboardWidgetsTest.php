<?php

namespace Tests\Feature;

use App\Filament\Widgets\AdherenceChart;
use App\Filament\Widgets\IncidentsByHourChart;
use App\Filament\Widgets\IncidentsByTypeChart;
use App\Filament\Widgets\OperationOverview;
use App\Filament\Widgets\RecurrenceChart;
use App\Models\IncidentType;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\SyncTestCase;

/**
 * Widgets de gráfico são lazy: a página abre sem executar getData(). Sem estes
 * testes, um erro no gráfico só apareceria para o gestor, na tela.
 */
class DashboardWidgetsTest extends SyncTestCase
{
    public static function widgets(): array
    {
        return [
            'indicadores' => [OperationOverview::class],
            'aderência' => [AdherenceChart::class],
            'ocorrências por hora' => [IncidentsByHourChart::class],
            'recorrência' => [RecurrenceChart::class],
            'ocorrências por tipo' => [IncidentsByTypeChart::class],
        ];
    }

    private function seedActivity(): void
    {
        $shiftUuid = (string) Str::uuid7();
        $patrolUuid = (string) Str::uuid7();

        $this->sync([
            $this->event('shift.start', ['post_id' => $this->post->id], uuid: $shiftUuid),
            $this->event('patrol.start', ['shift_uuid' => $shiftUuid, 'patrol_route_id' => $this->route->id], uuid: $patrolUuid),
            $this->event('patrol.scan', [
                'patrol_uuid' => $patrolUuid,
                'checkpoint_id' => $this->checkpoints[0]->id,
                'latitude' => (float) $this->checkpoints[0]->latitude,
                'longitude' => (float) $this->checkpoints[0]->longitude,
            ]),
            $this->event('incident.report', [
                'shift_uuid' => $shiftUuid,
                'incident_type_id' => IncidentType::create(['name' => 'Portão aberto'])->id,
                'description' => 'Portão dos fundos destrancado.',
                'severity' => 'high',
            ]),
            $this->event('patrol.end', ['patrol_uuid' => $patrolUuid]),
            $this->event('shift.end', ['shift_uuid' => $shiftUuid]),
        ])->assertOk();
    }

    #[DataProvider('widgets')]
    public function test_widget_renders_with_data(string $widget): void
    {
        $this->seedActivity();
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERVISOR]));

        Livewire::test($widget, ['pageFilters' => ['period' => '30', 'unit_id' => null]])
            ->assertOk();
    }

    #[DataProvider('widgets')]
    public function test_widget_renders_without_any_data(string $widget): void
    {
        // Unidade recém-criada, painel vazio: divisão por zero e array vazio são
        // o caminho mais provável de erro nos gráficos.
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERVISOR]));

        Livewire::test($widget, ['pageFilters' => ['period' => '7', 'unit_id' => null]])
            ->assertOk();
    }

    public function test_unit_manager_cannot_widen_the_scope_through_the_filter(): void
    {
        // O filtro é do cliente; a restrição tem de valer no servidor.
        $this->seedActivity();

        $otherUnit = Unit::create(['name' => 'Outra', 'code' => 'OUT']);

        $manager = User::factory()->create([
            'role' => User::ROLE_UNIT_MANAGER,
            'unit_id' => $otherUnit->id,
        ]);

        $this->actingAs($manager);

        // Mesmo pedindo "todas as unidades", só enxerga a dele - que não teve
        // operação nenhuma.
        $component = Livewire::test(OperationOverview::class, [
            'pageFilters' => ['period' => '30', 'unit_id' => null],
        ]);

        $component->assertOk();
        $component->assertSee('nenhum posto assumido');
        $component->assertDontSee('33.3%');
    }
}
