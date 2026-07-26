<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\ChecklistResponse;
use App\Models\DailyReport;
use App\Models\Incident;
use App\Models\PatrolScan;
use App\Models\RetentionRun;
use App\Models\Shift;
use App\Models\SyncBatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Expurgo de dados vencidos, conforme config/retention.php.
 *
 * Duas regras que orientam todas as decisões aqui:
 *
 * 1. Evidência vencida perde o binário, não a linha. Some a foto do
 *    colaborador; fica o registro de que existiu, com o hash. Isso preserva a
 *    rastreabilidade da ocorrência sem manter o dado pessoal.
 * 2. Documento (ocorrência, RDO) sobrevive ao dado operacional que o gerou. Um
 *    RO de três anos continua legível mesmo sem a ronda que o originou.
 */
class RetentionPurger
{
    public function policy(): array
    {
        return [
            'evidence_months' => (int) config('retention.evidence_months'),
            'patrol_months' => (int) config('retention.patrol_months'),
            'incident_years' => (int) config('retention.incident_years'),
            'sync_log_months' => (int) config('retention.sync_log_months'),
            'notification_months' => (int) config('retention.notification_months'),
        ];
    }

    public function run(bool $dryRun = false): RetentionRun
    {
        $policy = $this->policy();

        $run = RetentionRun::create([
            'dry_run' => $dryRun,
            'started_at' => now(),
            'policy' => $policy,
            'summary' => [],
        ]);

        try {
            $summary = $dryRun ? $this->preview($policy) : $this->purge($policy);

            $run->update(['summary' => $summary, 'finished_at' => now()]);
        } catch (\Throwable $e) {
            $run->update(['error' => $e->getMessage(), 'finished_at' => now()]);

            throw $e;
        }

        return $run->refresh();
    }

    // ------------------------------------------------------------------ datas

    private function evidenceCutoff(array $policy): Carbon
    {
        return now()->subMonths($policy['evidence_months']);
    }

    private function patrolCutoff(array $policy): Carbon
    {
        return now()->subMonths($policy['patrol_months']);
    }

    private function incidentCutoff(array $policy): Carbon
    {
        return now()->subYears($policy['incident_years']);
    }

    // ------------------------------------------------------------- simulação

    /**
     * Conta sem apagar. É como se confere a política antes de agendar o
     * expurgo em produção.
     */
    private function preview(array $policy): array
    {
        return [
            'incidents' => $this->expiredIncidents($policy)->count(),
            'shifts' => $this->expiredShifts($policy)->count(),
            'patrols' => $this->expiredShifts($policy)->withCount('patrols')->get()->sum('patrols_count'),
            'evidence_files' => $this->expiredEvidence($policy)->count(),
            'sync_batches' => $this->expiredSyncBatches($policy)->count(),
            'notifications' => $this->expiredNotifications($policy)->count(),
            'reports_marked' => $this->reportsToMark($policy)->count(),
        ];
    }

    // --------------------------------------------------------------- execução

    private function purge(array $policy): array
    {
        // Ordem deliberada: primeiro o que referencia, depois o referenciado.
        return [
            'incidents' => $this->purgeIncidents($policy),
            'shifts' => $this->purgeShifts($policy),
            'evidence_files' => $this->purgeEvidence($policy),
            'orphan_attachments' => $this->purgeOrphanAttachments(),
            'sync_batches' => $this->expiredSyncBatches($policy)->delete(),
            'notifications' => $this->expiredNotifications($policy)->delete(),
            'reports_marked' => $this->markPurgedReports($policy),
        ];
    }

    private function purgeIncidents(array $policy): int
    {
        $count = 0;

        $this->expiredIncidents($policy)
            ->with('attachments')
            ->chunkById(200, function ($incidents) use (&$count) {
                foreach ($incidents as $incident) {
                    $this->deleteFiles($incident->attachments);
                    $incident->attachments()->delete();
                    $incident->delete();
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Apagar o turno cascateia rondas, leituras e respostas de checklist. As
     * evidências ligadas a essas leituras são polimórficas e não têm cascata —
     * precisam ser removidas antes, senão sobra linha órfã apontando para um id
     * que não existe mais.
     */
    private function purgeShifts(array $policy): int
    {
        $count = 0;

        $this->expiredShifts($policy)->chunkById(100, function ($shifts) use (&$count) {
            foreach ($shifts as $shift) {
                $scanIds = PatrolScan::query()
                    ->whereIn('patrol_id', $shift->patrols()->select('id'))
                    ->pluck('id');

                if ($scanIds->isNotEmpty()) {
                    $responseIds = ChecklistResponse::whereIn('patrol_scan_id', $scanIds)->pluck('id');

                    $this->deleteAttachmentsOf(PatrolScan::class, $scanIds->all());
                    $this->deleteAttachmentsOf(ChecklistResponse::class, $responseIds->all());
                }

                $shift->delete();
                $count++;
            }
        });

        return $count;
    }

    /**
     * Evidência vencida cujo dono ainda está no prazo (uma ocorrência de dois
     * anos, por exemplo): apaga o arquivo, mantém a linha marcada.
     */
    private function purgeEvidence(array $policy): int
    {
        $count = 0;

        $this->expiredEvidence($policy)->chunkById(200, function ($attachments) use (&$count) {
            foreach ($attachments as $attachment) {
                if (filled($attachment->path)) {
                    Storage::delete($attachment->path);
                }

                $attachment->update([
                    'path' => null,
                    'status' => 'purged',
                    // sha256 permanece: prova o que existiu sem guardar o dado.
                ]);

                $count++;
            }
        });

        return $count;
    }

    /**
     * Rede de segurança: evidência cujo dono desapareceu por algum caminho que
     * não passou pelo expurgo (exclusão manual, cascata antiga).
     */
    private function purgeOrphanAttachments(): int
    {
        $count = 0;

        Attachment::query()
            ->whereNotNull('attachable_type')
            ->chunkById(500, function ($attachments) use (&$count) {
                foreach ($attachments as $attachment) {
                    if ($attachment->attachable !== null) {
                        continue;
                    }

                    if (filled($attachment->path)) {
                        Storage::delete($attachment->path);
                    }

                    $attachment->delete();
                    $count++;
                }
            });

        return $count;
    }

    /**
     * RDO fechado cuja data já teve os dados de campo expurgados. Sem esta
     * marca, a verificação de integridade acusaria divergência para sempre.
     */
    private function markPurgedReports(array $policy): int
    {
        return $this->reportsToMark($policy)->update(['data_purged_at' => now()]);
    }

    // ---------------------------------------------------------------- queries

    private function expiredIncidents(array $policy)
    {
        return Incident::query()->where('occurred_at', '<', $this->incidentCutoff($policy));
    }

    private function expiredShifts(array $policy)
    {
        return Shift::query()
            ->where('status', 'closed')
            ->where('started_at', '<', $this->patrolCutoff($policy));
    }

    private function expiredEvidence(array $policy)
    {
        return Attachment::query()
            ->where('status', '!=', 'purged')
            ->whereNotNull('path')
            ->where(fn ($q) => $q
                ->where('captured_at', '<', $this->evidenceCutoff($policy))
                ->orWhere(fn ($inner) => $inner
                    ->whereNull('captured_at')
                    ->where('created_at', '<', $this->evidenceCutoff($policy))));
    }

    private function expiredSyncBatches(array $policy)
    {
        return SyncBatch::query()
            ->where('created_at', '<', now()->subMonths($policy['sync_log_months']));
    }

    private function expiredNotifications(array $policy)
    {
        return DB::table('notifications')
            ->where('created_at', '<', now()->subMonths($policy['notification_months']));
    }

    private function reportsToMark(array $policy)
    {
        return DailyReport::query()
            ->whereNull('data_purged_at')
            ->where('report_date', '<', $this->patrolCutoff($policy)->toDateString());
    }

    // ---------------------------------------------------------------- helpers

    private function deleteFiles(iterable $attachments): void
    {
        foreach ($attachments as $attachment) {
            if (filled($attachment->path)) {
                Storage::delete($attachment->path);
            }
        }
    }

    private function deleteAttachmentsOf(string $type, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $attachments = Attachment::query()
            ->where('attachable_type', $type)
            ->whereIn('attachable_id', $ids)
            ->get();

        $this->deleteFiles($attachments);

        Attachment::query()
            ->where('attachable_type', $type)
            ->whereIn('attachable_id', $ids)
            ->delete();
    }
}
