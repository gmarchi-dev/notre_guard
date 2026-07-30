<?php

namespace Tests\Feature\Api;

use App\Models\Incident;
use App\Models\IncidentType;
use Illuminate\Support\Str;

/**
 * Pacote que o aparelho baixa no início do turno.
 *
 * Depois disto o app funciona sem rede até o fim do turno - o que estiver
 * faltando aqui simplesmente não existe em campo.
 */
class BootstrapTest extends SyncTestCase
{
    private function bootstrap(?string $token = null): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer '.($token ?? $this->login()),
            'X-Device-Id' => $this->deviceId,
        ])->getJson(route('api.bootstrap'));
    }

    private function incidentType(string $group, string $name, string $severity = 'medium'): IncidentType
    {
        $parent = IncidentType::firstOrCreate(['name' => $group, 'parent_id' => null]);

        return IncidentType::create([
            'name' => $name,
            'parent_id' => $parent->id,
            'default_severity' => $severity,
            'default_classification' => 'prevention',
        ]);
    }

    /** A numeração RO NNN/AAAA é alocada no servidor e é obrigatória. */
    private function makeIncident(array $attributes): Incident
    {
        $unit = \App\Models\Unit::findOrFail($attributes['unit_id']);
        $number = (new \App\Services\IncidentNumberAllocator)
            ->allocate($unit, (int) date('Y'));

        return Incident::create([
            'uuid' => (string) Str::uuid7(),
            ...$number,
            ...$attributes,
        ]);
    }

    private function incident(IncidentType $type, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->makeIncident([
                'unit_id' => $this->unit->id,
                'incident_type_id' => $type->id,
                'reported_by_id' => $this->guard->id,
                'occurred_at' => now()->subDays(3),
                'received_at' => now()->subDays(3),
                'severity' => 'medium',
                'classification' => 'prevention',
                'description' => 'Registro de teste',
            ]);
        }
    }

    public function test_incident_types_carry_the_group_separately_from_the_leaf(): void
    {
        // O aparelho monta a escolha em duas etapas. Achatado num rótulo só, a
        // hierarquia existia apenas como um caractere no meio da string.
        $this->incidentType('Patrimônio', 'Furto ou tentativa', 'high');

        $type = $this->bootstrap()->assertOk()->json('incident_types.0');

        $this->assertSame('Patrimônio', $type['group']);
        $this->assertSame('Furto ou tentativa', $type['name']);
        $this->assertSame('high', $type['default_severity']);
        $this->assertStringContainsString('Furto', $type['label'], 'o rótulo completo continua servindo ao RDO');
    }

    public function test_frequent_types_come_from_what_this_unit_actually_registered(): void
    {
        // Atalho medido, não adivinhado.
        $vazamento = $this->incidentType('Infraestrutura', 'Vazamento');
        $camera = $this->incidentType('Sistemas', 'Câmera inoperante');
        $this->incidentType('Pessoas', 'Conduta inadequada');

        $this->incident($camera, 3);
        $this->incident($vazamento, 5);

        $frequent = $this->bootstrap()->assertOk()->json('frequent_incident_type_ids');

        $this->assertSame([$vazamento->id, $camera->id], $frequent, 'o mais registrado vem primeiro');
    }

    public function test_a_new_installation_gets_no_shortcuts_instead_of_wrong_ones(): void
    {
        // Sem histórico, a seção some - melhor que sugerir o que ninguém usa.
        $this->incidentType('Patrimônio', 'Furto ou tentativa');

        $this->assertSame([], $this->bootstrap()->assertOk()->json('frequent_incident_type_ids'));
    }

    public function test_shortcuts_ignore_what_happened_in_another_unit(): void
    {
        $type = $this->incidentType('Infraestrutura', 'Vazamento');

        $other = \App\Models\Unit::create(['name' => 'Filial', 'code' => 'FIL']);

        $this->makeIncident([
            'unit_id' => $other->id,
            'incident_type_id' => $type->id,
            'reported_by_id' => $this->guard->id,
            'occurred_at' => now()->subDay(),
            'received_at' => now()->subDay(),
            'severity' => 'medium',
            'classification' => 'prevention',
            'description' => 'Ocorrência da filial',
        ]);

        $this->assertSame([], $this->bootstrap()->assertOk()->json('frequent_incident_type_ids'));
    }

    public function test_shortcuts_forget_what_is_older_than_ninety_days(): void
    {
        // O que a equipe registrava no ano passado não é o atalho de hoje.
        $type = $this->incidentType('Infraestrutura', 'Vazamento');

        $this->makeIncident([
            'unit_id' => $this->unit->id,
            'incident_type_id' => $type->id,
            'reported_by_id' => $this->guard->id,
            'occurred_at' => now()->subDays(120),
            'received_at' => now()->subDays(120),
            'severity' => 'medium',
            'classification' => 'prevention',
            'description' => 'Ocorrência antiga',
        ]);

        $this->assertSame([], $this->bootstrap()->assertOk()->json('frequent_incident_type_ids'));
    }

    public function test_checkpoints_carry_the_coordinates_the_device_needs_to_measure_distance(): void
    {
        // Sem isto o aviso de desvio e a distância até o próximo ponto não têm
        // com o que contar.
        $checkpoint = $this->bootstrap()->assertOk()->json('checkpoints.0');

        $this->assertIsFloat($checkpoint['latitude']);
        $this->assertIsFloat($checkpoint['longitude']);
        $this->assertSame(50, $checkpoint['radius_m']);
    }

    public function test_bootstrap_requires_authentication(): void
    {
        $this->getJson(route('api.bootstrap'))->assertUnauthorized();
    }
}
