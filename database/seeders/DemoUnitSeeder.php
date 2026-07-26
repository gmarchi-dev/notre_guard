<?php

namespace Database\Seeders;

use App\Models\Checkpoint;
use App\Models\ChecklistItem;
use App\Models\ChecklistTemplate;
use App\Models\PatrolRoute;
use App\Models\PatrolRouteSchedule;
use App\Models\Post;
use App\Models\SecurityGuard;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Unidade de demonstração para desenvolvimento e treinamento.
 * Coordenadas aproximadas de Campinas/SP.
 */
class DemoUnitSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::firstOrCreate(
            ['code' => 'SEDE'],
            [
                'name' => 'Unidade Sede',
                'address' => 'Campinas/SP',
                'latitude' => -22.9056,
                'longitude' => -47.0608,
                'radius_m' => 300,
            ],
        );

        $posts = [
            ['Portaria Principal', 'reception'],
            ['Guarita Estacionamento', 'fixed'],
        ];

        foreach ($posts as [$name, $kind]) {
            Post::firstOrCreate(
                ['unit_id' => $unit->id, 'name' => $name],
                ['kind' => $kind, 'latitude' => -22.9056, 'longitude' => -47.0608],
            );
        }

        $checklist = ChecklistTemplate::firstOrCreate(
            ['unit_id' => $unit->id, 'name' => 'Verificação padrão de perímetro'],
            ['description' => 'Aplicado na passagem pelos pontos externos.'],
        );

        $items = [
            'Portão/porta trancado',
            'Iluminação funcionando',
            'Sem sinais de violação',
            'Área livre de pessoas não autorizadas',
        ];

        foreach ($items as $position => $label) {
            ChecklistItem::firstOrCreate(
                ['checklist_template_id' => $checklist->id, 'label' => $label],
                ['position' => $position + 1],
            );
        }

        $checkpoints = [
            ['PC-01', 'Portão principal', -22.90560, -47.06080],
            ['PC-02', 'Estacionamento — fundo', -22.90600, -47.06120],
            ['PC-03', 'Bloco A — entrada', -22.90520, -47.06050],
            ['PC-04', 'Bloco B — corredor externo', -22.90490, -47.06010],
            ['PC-05', 'Casa de máquinas', -22.90610, -47.06040],
            ['PC-06', 'Quadra poliesportiva', -22.90540, -47.06160],
        ];

        $created = [];

        foreach ($checkpoints as [$code, $name, $lat, $lng]) {
            $created[] = Checkpoint::firstOrCreate(
                ['unit_id' => $unit->id, 'code' => $code],
                [
                    'name' => $name,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'radius_m' => 50,
                    'checklist_template_id' => $checklist->id,
                ],
            );
        }

        $route = PatrolRoute::firstOrCreate(
            ['unit_id' => $unit->id, 'name' => 'Ronda perimetral completa'],
            [
                'description' => 'Volta completa pelo perímetro, sentido horário.',
                'ordered' => true,
                'expected_duration_min' => 40,
                'tolerance_min' => 15,
            ],
        );

        foreach ($created as $position => $checkpoint) {
            $route->checkpoints()->syncWithoutDetaching([
                $checkpoint->id => ['position' => $position + 1, 'required' => true],
            ]);
        }

        foreach ([['Ronda noturna', '22:00', '23:00'], ['Ronda madrugada', '02:00', '03:00']] as [$label, $start, $end]) {
            PatrolRouteSchedule::firstOrCreate(
                ['patrol_route_id' => $route->id, 'label' => $label],
                ['window_start' => $start, 'window_end' => $end, 'weekdays' => [0, 1, 2, 3, 4, 5, 6]],
            );
        }

        $guardUser = User::firstOrCreate(
            ['email' => 'vigilante@notreguard.local'],
            [
                'name' => 'Vigilante Demonstração',
                'password' => Hash::make('vigilante1234'),
                'role' => User::ROLE_GUARD,
                // Vigilante de demonstração fica na portaria, então recebe a
                // permissão de chaves. Em produção isso é concedido caso a caso.
                'permissions' => [User::PERMISSION_KEYS],
            ],
        );

        SecurityGuard::firstOrCreate(
            ['user_id' => $guardUser->id],
            [
                'registration' => 'VIG-001',
                'default_unit_id' => $unit->id,
                'refresher_valid_until' => now()->addYear(),
            ],
        );
    }
}
