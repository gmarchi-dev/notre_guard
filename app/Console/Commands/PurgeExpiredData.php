<?php

namespace App\Console\Commands;

use App\Services\RetentionPurger;
use Illuminate\Console\Command;

class PurgeExpiredData extends Command
{
    protected $signature = 'notre-guard:purge-data
                            {--dry-run : Apenas conta o que seria eliminado}';

    protected $description = 'Elimina dados vencidos conforme a política de retenção (LGPD)';

    public function handle(RetentionPurger $purger): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $policy = $purger->policy();

        $this->components->info($dryRun
            ? 'Simulação de expurgo (nada será apagado)'
            : 'Expurgo de dados vencidos');

        $this->table(
            ['Política', 'Prazo'],
            [
                ['Evidências (foto/vídeo)', $policy['evidence_months'].' meses'],
                ['Turnos, rondas e leituras', $policy['patrol_months'].' meses'],
                ['Ocorrências e RDO', $policy['incident_years'].' anos'],
                ['Logs de sincronização', $policy['sync_log_months'].' meses'],
                ['Notificações', $policy['notification_months'].' meses'],
            ],
        );

        $run = $purger->run($dryRun);

        $labels = [
            'incidents' => 'Ocorrências eliminadas',
            'shifts' => 'Turnos eliminados (com rondas e leituras)',
            'patrols' => 'Rondas alcançadas',
            'evidence_files' => 'Arquivos de evidência apagados',
            'orphan_attachments' => 'Evidências órfãs removidas',
            'sync_batches' => 'Lotes de sincronização eliminados',
            'notifications' => 'Notificações eliminadas',
            'reports_marked' => 'RDOs marcados como expurgados',
        ];

        $rows = [];

        foreach ($run->summary as $key => $value) {
            $rows[] = [$labels[$key] ?? $key, $value];
        }

        $this->table([$dryRun ? 'Seria eliminado' : 'Eliminado', 'Quantidade'], $rows);

        $this->components->info(sprintf(
            'Execução #%d registrada em retention_runs.',
            $run->id,
        ));

        return self::SUCCESS;
    }
}
