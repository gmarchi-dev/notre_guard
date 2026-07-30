<?php

namespace App\Services;

use App\Models\ChecklistResponse;
use App\Models\Incident;
use App\Models\Patrol;
use App\Models\PatrolScan;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Indicadores operacionais de um período, opcionalmente de uma unidade.
 *
 * As agregações por dia e por hora são feitas em PHP e não no banco: `HOUR()` e
 * `strftime()` divergem entre MySQL e SQLite, e o volume aqui é de centenas de
 * registros por mês - não vale trocar portabilidade por microssegundos.
 */
class OperationMetrics
{
    public function __construct(
        private readonly Carbon $from,
        private readonly Carbon $to,
        private readonly ?int $unitId = null,
    ) {}

    public static function forPeriod(Carbon $from, Carbon $to, ?int $unitId = null): self
    {
        return new self($from->copy()->startOfDay(), $to->copy()->endOfDay(), $unitId);
    }

    // ---------------------------------------------------------------- números

    /**
     * Aderência de ronda: pontos lidos ÷ pontos previstos, em %.
     * É o indicador que o contrato de segurança cobra.
     */
    public function adherence(): ?float
    {
        $totals = $this->patrols()
            ->selectRaw('COALESCE(SUM(expected_checkpoints), 0) as expected, COALESCE(SUM(scanned_checkpoints), 0) as scanned')
            ->first();

        if (! $totals || (int) $totals->expected === 0) {
            return null;
        }

        return round((int) $totals->scanned / (int) $totals->expected * 100, 1);
    }

    public function patrolCounts(): array
    {
        $patrols = $this->patrols()->get(['status', 'expected_checkpoints', 'scanned_checkpoints']);

        return [
            'total' => $patrols->count(),
            'completed' => $patrols->where('status', 'completed')->count(),
            'incomplete' => $patrols->filter(
                fn (Patrol $p) => $p->scanned_checkpoints < $p->expected_checkpoints,
            )->count(),
        ];
    }

    public function incidentCounts(): array
    {
        $incidents = $this->incidents()->get(['severity', 'status', 'classification']);

        return [
            'total' => $incidents->count(),
            'open' => $incidents->whereNotIn('status', ['closed'])->count(),
            'critical' => $incidents->whereIn('severity', ['high', 'critical'])->count(),
            'loss' => $incidents->where('classification', 'loss')->count(),
        ];
    }

    public function openShifts(): int
    {
        return Shift::query()
            ->where('status', 'open')
            ->when($this->unitId, fn (Builder $q) => $q->where('unit_id', $this->unitId))
            ->count();
    }

    public function scanDeviationRate(): ?float
    {
        $scans = $this->scans()->get(['deviations']);

        if ($scans->isEmpty()) {
            return null;
        }

        $withDeviation = $scans->filter(fn (PatrolScan $s) => filled($s->deviations))->count();

        return round($withDeviation / $scans->count() * 100, 1);
    }

    // ----------------------------------------------------------------- séries

    /**
     * Aderência dia a dia. Dias sem ronda ficam como null, para o gráfico não
     * desenhar uma queda a zero onde na verdade não houve operação.
     *
     * @return array<string, float|null> data (Y-m-d) => aderência
     */
    public function adherenceByDay(): array
    {
        $rows = $this->patrols()
            ->get(['started_at', 'expected_checkpoints', 'scanned_checkpoints'])
            ->groupBy(fn (Patrol $p) => $p->started_at->toDateString());

        $series = [];

        foreach ($this->dayRange() as $day) {
            $patrols = $rows->get($day);
            $expected = $patrols?->sum('expected_checkpoints') ?? 0;

            $series[$day] = $expected > 0
                ? round($patrols->sum('scanned_checkpoints') / $expected * 100, 1)
                : null;
        }

        return $series;
    }

    /**
     * Recorrência por horário: em que faixas do dia as ocorrências acontecem.
     * É o que orienta redesenho de rota e reforço de posto.
     *
     * @return array<int, int> hora (0-23) => quantidade
     */
    public function incidentsByHour(): array
    {
        $counts = array_fill(0, 24, 0);

        foreach ($this->incidents()->get(['occurred_at']) as $incident) {
            $counts[(int) $incident->occurred_at->hour]++;
        }

        return $counts;
    }

    /** @return Collection<int, object{label: string, total: int}> */
    public function incidentsByType(int $limit = 8): Collection
    {
        return $this->incidents()
            ->with('type.parent')
            ->get(['incident_type_id'])
            ->groupBy(fn (Incident $i) => $i->type?->fullName() ?? '—')
            ->map->count()
            ->sortDesc()
            ->take($limit)
            ->map(fn (int $total, string $label) => (object) ['label' => $label, 'total' => $total])
            ->values();
    }

    /**
     * Pontos que mais produzem não conformidade - onde o problema é crônico.
     *
     * @return Collection<int, object{label: string, total: int}>
     */
    public function topNonConformingCheckpoints(int $limit = 8): Collection
    {
        return ChecklistResponse::query()
            ->where('answer', 'nonconforming')
            ->whereHas('patrolScan.patrol', fn (Builder $q) => $this->constrainPatrol($q))
            ->with('patrolScan.checkpoint')
            ->get()
            ->groupBy(fn (ChecklistResponse $r) => $r->patrolScan?->checkpoint
                ? "{$r->patrolScan->checkpoint->code} - {$r->patrolScan->checkpoint->name}"
                : '—')
            ->map->count()
            ->sortDesc()
            ->take($limit)
            ->map(fn (int $total, string $label) => (object) ['label' => $label, 'total' => $total])
            ->values();
    }

    /** @return array<string, int> tipo de desvio => quantidade */
    public function deviationBreakdown(): array
    {
        $counts = [];

        foreach ($this->scans()->get(['deviations']) as $scan) {
            foreach ($scan->deviations ?? [] as $deviation) {
                $counts[$deviation] = ($counts[$deviation] ?? 0) + 1;
            }
        }

        arsort($counts);

        return $counts;
    }

    // ---------------------------------------------------------------- helpers

    /** @return list<string> */
    public function dayRange(): array
    {
        $days = [];

        for ($day = $this->from->copy(); $day->lte($this->to); $day->addDay()) {
            $days[] = $day->toDateString();
        }

        return $days;
    }

    private function patrols(): Builder
    {
        return $this->constrainPatrol(Patrol::query());
    }

    private function constrainPatrol(Builder $query): Builder
    {
        return $query
            ->whereBetween('started_at', [$this->from, $this->to])
            ->when($this->unitId, fn (Builder $q) => $q->where('unit_id', $this->unitId));
    }

    private function scans(): Builder
    {
        return PatrolScan::query()
            ->whereHas('patrol', fn (Builder $q) => $this->constrainPatrol($q));
    }

    private function incidents(): Builder
    {
        return Incident::query()
            ->whereBetween('occurred_at', [$this->from, $this->to])
            ->when($this->unitId, fn (Builder $q) => $q->where('unit_id', $this->unitId));
    }
}
