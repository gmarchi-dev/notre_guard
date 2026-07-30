<?php

namespace Database\Seeders;

use App\Models\IncidentType;
use Illuminate\Database\Seeder;

/**
 * Taxonomia inicial de ocorrências. É um ponto de partida para a Fase 0 -
 * a árvore definitiva sai da conversa com a equipe de segurança.
 */
class IncidentTypeSeeder extends Seeder
{
    private const TREE = [
        'Patrimônio' => [
            ['Furto ou tentativa', 'loss', 'high', true],
            ['Dano ao patrimônio', 'loss', 'medium', false],
            ['Porta ou portão aberto', 'prevention', 'medium', false],
            ['Janela destrancada', 'prevention', 'low', false],
        ],
        'Pessoas' => [
            ['Pessoa não autorizada', 'prevention', 'high', true],
            ['Conduta inadequada', 'prevention', 'medium', false],
            ['Acidente ou mal súbito', 'loss', 'critical', true],
        ],
        'Infraestrutura' => [
            ['Iluminação com defeito', 'prevention', 'low', false],
            ['Vazamento', 'loss', 'medium', false],
            ['Falha de energia', 'prevention', 'medium', false],
            ['Princípio de incêndio', 'loss', 'critical', true],
        ],
        'Sistemas de segurança' => [
            ['Câmera inoperante', 'prevention', 'medium', true],
            ['Alarme disparado', 'prevention', 'high', true],
            ['Controle de acesso com falha', 'prevention', 'medium', false],
        ],
        'Operacional' => [
            ['Ronda não realizada', 'prevention', 'medium', true],
            ['Equipamento em falta', 'prevention', 'low', false],
            ['Registro em atraso', 'prevention', 'low', false],
        ],
    ];

    public function run(): void
    {
        foreach (self::TREE as $parentName => $children) {
            $parent = IncidentType::firstOrCreate(
                ['name' => $parentName, 'parent_id' => null],
            );

            foreach ($children as [$name, $classification, $severity, $notify]) {
                IncidentType::firstOrCreate(
                    ['name' => $name, 'parent_id' => $parent->id],
                    [
                        'default_classification' => $classification,
                        'default_severity' => $severity,
                        'notify_supervision' => $notify,
                    ],
                );
            }
        }
    }
}
