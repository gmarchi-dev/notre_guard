<?php

namespace App\Filament\Widgets\Concerns;

use App\Services\OperationMetrics;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

/**
 * Traduz os filtros do painel em um OperationMetrics.
 *
 * O filtro de unidade é sempre reforçado no servidor: um gestor de unidade não
 * pode ver números de outra unidade nem manipulando o estado do Livewire.
 */
trait ReadsDashboardFilters
{
    use InteractsWithPageFilters;

    protected function metrics(): OperationMetrics
    {
        $days = (int) ($this->pageFilters['period'] ?? 30);
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;

        $user = auth()->user();

        $unitId = $user->isScopedToUnit()
            ? $user->unit_id
            : (filled($this->pageFilters['unit_id'] ?? null) ? (int) $this->pageFilters['unit_id'] : null);

        return OperationMetrics::forPeriod(
            Carbon::today()->subDays($days - 1),
            Carbon::today(),
            $unitId,
        );
    }

    protected function periodLabel(): string
    {
        $days = (int) ($this->pageFilters['period'] ?? 30);

        return "últimos {$days} dias";
    }
}
