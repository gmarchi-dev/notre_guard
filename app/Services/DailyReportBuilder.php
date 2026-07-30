<?php

namespace App\Services;

use App\Models\ChecklistResponse;
use App\Models\DailyReport;
use App\Models\Incident;
use App\Models\KeyLoan;
use App\Models\Patrol;
use App\Models\PatrolScan;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Monta o RDO - Relatório Diário de Ocorrências - de uma unidade em uma data.
 *
 * Enquanto está em rascunho, o conteúdo é recalculado a cada visualização: é um
 * espelho do que existe no banco. No fechamento vira fotografia, com hash do
 * conteúdo e PDF gerado.
 */
class DailyReportBuilder
{
    /**
     * Garante o RDO da data e atualiza o rascunho com os números atuais.
     */
    public function buildOrUpdate(Unit $unit, Carbon $date): DailyReport
    {
        // whereDate e não igualdade: o cast "date" grava 'Y-m-d H:i:s', então
        // comparar com 'Y-m-d' não casa e o RDO acabaria duplicado - o índice
        // único derruba a segunda gravação.
        $report = DailyReport::query()
            ->where('unit_id', $unit->id)
            ->whereDate('report_date', $date->toDateString())
            ->first()
            ?? new DailyReport([
                'unit_id' => $unit->id,
                'report_date' => $date->copy()->startOfDay(),
            ]);

        // RDO fechado não se recalcula: ele é o registro do que foi fechado.
        // Idem para rascunho cuja data já foi expurgada - recalcular só zeraria
        // os números de um período que não existe mais.
        if ($report->exists && ($report->isClosed() || $report->dataWasPurged())) {
            return $report;
        }

        $summary = $this->summaryFor($unit, $date);

        $report->fill([
            'status' => 'draft',
            'summary' => $summary,
        ])->save();

        return $report;
    }

    /**
     * @throws RuntimeException quando ainda há turno aberto na data
     */
    public function close(DailyReport $report, User $user): DailyReport
    {
        if ($report->isClosed()) {
            return $report;
        }

        $openShifts = $this->shiftsQuery($report->unit, $report->report_date)
            ->where('status', 'open')
            ->count();

        if ($openShifts > 0) {
            // Fechar com turno em aberto produziria um RDO que nasce
            // desatualizado - os registros daquele turno ainda vão chegar.
            throw new RuntimeException(
                "Ainda há {$openShifts} turno(s) aberto(s) nesta data. Feche o RDO após o encerramento dos turnos.",
            );
        }

        $summary = $this->summaryFor($report->unit, $report->report_date);

        $report->fill([
            'summary' => $summary,
            'status' => 'closed',
            'closed_by_user_id' => $user->id,
            'closed_at' => now(),
            'content_hash' => $this->hash($summary),
        ])->save();

        $report->update(['pdf_path' => $this->renderPdf($report)]);

        return $report;
    }

    /**
     * Registros podem chegar dias depois - um aparelho que ficou sem rede. Se o
     * conteúdo atual não bate mais com o hash selado, o RDO fechado deixou de
     * refletir a realidade e a supervisão precisa saber.
     */
    public function hasLateRecords(DailyReport $report): bool
    {
        if (! $report->isClosed() || blank($report->content_hash)) {
            return false;
        }

        // Data já expurgada pela retenção: recalcular daria vazio e acusaria
        // divergência para sempre. O documento segue selado como está.
        if ($report->dataWasPurged()) {
            return false;
        }

        return $this->hash($this->summaryFor($report->unit, $report->report_date)) !== $report->content_hash;
    }

    public function hash(array $summary): string
    {
        return hash('sha256', json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public function summaryFor(Unit $unit, Carbon $date): array
    {
        return [
            'shifts' => $this->shifts($unit, $date),
            'patrols' => $this->patrols($unit, $date),
            'scans' => $this->scans($unit, $date),
            'nonconformities' => $this->nonconformities($unit, $date),
            'incidents' => $this->incidents($unit, $date),
            'keys' => $this->keys($unit, $date),
        ];
    }

    /**
     * Movimentação de chaves do dia e o que ficou fora do quadro. A chave não
     * devolvida é a pendência que a portaria passa para o turno seguinte - é
     * exatamente o tipo de coisa que o RDO existe para registrar.
     */
    private function keys(Unit $unit, Carbon $date): array
    {
        $released = KeyLoan::query()
            ->where('unit_id', $unit->id)
            ->whereDate('released_at', $date->toDateString())
            ->with(['keyItem', 'holder'])
            ->orderBy('released_at')
            ->get();

        $endOfDay = $date->copy()->endOfDay();

        // "Em aberto" é medido no fim do dia do relatório, não agora: um RDO de
        // ontem não pode mudar porque a chave voltou hoje de manhã.
        $outstanding = KeyLoan::query()
            ->where('unit_id', $unit->id)
            ->where('released_at', '<=', $endOfDay)
            ->where(fn ($q) => $q->whereNull('returned_at')->orWhere('returned_at', '>', $endOfDay))
            ->with(['keyItem', 'holder'])
            ->orderBy('due_at')
            ->get();

        return [
            'released' => $released->count(),
            'returned' => $released->whereNotNull('returned_at')->count(),
            'outstanding' => $outstanding->count(),
            'overdue' => $outstanding->filter(fn (KeyLoan $l) => $l->due_at->lte($endOfDay))->count(),
            'items' => $outstanding->map(fn (KeyLoan $loan) => [
                'code' => $loan->keyItem?->code ?? '—',
                'name' => $loan->keyItem?->name ?? '—',
                'holder' => $loan->holder?->name ?? '—',
                'released_at' => $loan->released_at->format('d/m H:i'),
                'due_at' => $loan->due_at->format('d/m H:i'),
                'overdue' => $loan->due_at->lte($endOfDay),
            ])->all(),
        ];
    }

    private function shiftsQuery(Unit $unit, Carbon $date)
    {
        return Shift::query()
            ->where('unit_id', $unit->id)
            ->whereDate('started_at', $date->toDateString());
    }

    private function shifts(Unit $unit, Carbon $date): array
    {
        $shifts = $this->shiftsQuery($unit, $date)
            ->with(['securityGuard.user', 'post'])
            ->orderBy('started_at')
            ->get();

        return [
            'total' => $shifts->count(),
            'open' => $shifts->where('status', 'open')->count(),
            'items' => $shifts->map(fn (Shift $shift) => [
                'guard' => $shift->securityGuard?->user?->name ?? '—',
                'registration' => $shift->securityGuard?->registration,
                'post' => $shift->post?->name ?? '—',
                'started_at' => $shift->started_at->format('H:i'),
                'ended_at' => $shift->ended_at?->format('H:i'),
                'handover_notes' => $shift->handover_notes,
            ])->all(),
        ];
    }

    private function patrols(Unit $unit, Carbon $date): array
    {
        $patrols = Patrol::query()
            ->where('unit_id', $unit->id)
            ->whereDate('started_at', $date->toDateString())
            ->with(['patrolRoute', 'shift.securityGuard.user'])
            ->orderBy('started_at')
            ->get();

        $expected = $patrols->sum('expected_checkpoints');
        $scanned = $patrols->sum('scanned_checkpoints');

        return [
            'total' => $patrols->count(),
            'completed' => $patrols->where('status', 'completed')->count(),
            'incomplete' => $patrols->filter(
                fn (Patrol $p) => $p->scanned_checkpoints < $p->expected_checkpoints,
            )->count(),
            'expected_checkpoints' => $expected,
            'scanned_checkpoints' => $scanned,
            'adherence' => $expected > 0 ? round($scanned / $expected * 100, 1) : null,
            'items' => $patrols->map(fn (Patrol $patrol) => [
                'route' => $patrol->patrolRoute?->name ?? '—',
                'guard' => $patrol->shift?->securityGuard?->user?->name ?? '—',
                'started_at' => $patrol->started_at->format('H:i'),
                'ended_at' => $patrol->ended_at?->format('H:i'),
                'scanned' => $patrol->scanned_checkpoints,
                'expected' => $patrol->expected_checkpoints,
                'status' => $patrol->status,
            ])->all(),
        ];
    }

    private function scans(Unit $unit, Carbon $date): array
    {
        $scans = PatrolScan::query()
            ->whereHas('patrol', fn ($q) => $q
                ->where('unit_id', $unit->id)
                ->whereDate('started_at', $date->toDateString()))
            ->get(['deviations', 'outcome']);

        $byDeviation = [];

        foreach ($scans as $scan) {
            foreach ($scan->deviations ?? [] as $deviation) {
                $byDeviation[$deviation] = ($byDeviation[$deviation] ?? 0) + 1;
            }
        }

        ksort($byDeviation);

        return [
            'total' => $scans->count(),
            'skipped' => $scans->where('outcome', 'skipped')->count(),
            'with_deviation' => $scans->filter(fn (PatrolScan $s) => filled($s->deviations))->count(),
            'by_deviation' => $byDeviation,
        ];
    }

    private function nonconformities(Unit $unit, Carbon $date): array
    {
        $responses = ChecklistResponse::query()
            ->where('answer', 'nonconforming')
            ->whereHas('patrolScan.patrol', fn ($q) => $q
                ->where('unit_id', $unit->id)
                ->whereDate('started_at', $date->toDateString()))
            ->with(['item', 'patrolScan.checkpoint'])
            ->get();

        return [
            'total' => $responses->count(),
            'items' => $responses->map(fn (ChecklistResponse $response) => [
                'checkpoint' => $response->patrolScan?->checkpoint?->code ?? '—',
                'item' => $response->item?->label ?? '—',
                'note' => $response->note,
                'at' => $response->patrolScan?->occurred_at?->format('H:i'),
            ])->all(),
        ];
    }

    private function incidents(Unit $unit, Carbon $date): array
    {
        $incidents = Incident::query()
            ->where('unit_id', $unit->id)
            ->whereDate('occurred_at', $date->toDateString())
            ->with(['type.parent', 'reportedBy.user'])
            ->orderBy('occurred_at')
            ->get();

        return [
            'total' => $incidents->count(),
            'by_severity' => $incidents->groupBy('severity')->map->count()->sortKeys()->all(),
            'by_classification' => $incidents->groupBy('classification')->map->count()->sortKeys()->all(),
            'items' => $incidents->map(fn (Incident $incident) => [
                'number' => $incident->number,
                'occurred_at' => $incident->occurred_at->format('H:i'),
                'type' => $incident->type?->fullName() ?? '—',
                'severity' => $incident->severity,
                'classification' => $incident->classification,
                'location' => $incident->location,
                'description' => $incident->description,
                'actions_taken' => $incident->actions_taken,
                'reported_by' => $incident->reportedBy?->user?->name ?? '—',
            ])->all(),
        ];
    }

    private function renderPdf(DailyReport $report): string
    {
        $pdf = Pdf::loadView('pdf.daily-report', [
            'report' => $report->loadMissing(['unit', 'closedBy']),
            'summary' => $report->summary,
        ])->setPaper('a4');

        $path = sprintf(
            'reports/%s/RDO-%s-%s.pdf',
            $report->report_date->format('Y/m'),
            $report->unit->code,
            $report->report_date->format('Y-m-d'),
        );

        Storage::put($path, $pdf->output());

        return $path;
    }
}
