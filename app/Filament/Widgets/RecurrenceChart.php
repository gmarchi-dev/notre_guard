<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsDashboardFilters;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

/**
 * Onde o problema é crônico: pontos que mais produzem não conformidade de
 * checklist. Orienta manutenção e redesenho de rota.
 */
class RecurrenceChart extends ChartWidget
{
    use ReadsDashboardFilters;

    protected ?string $heading = 'Pontos com mais não conformidade';

    protected ?string $description = 'Itens de checklist marcados como não conformes, por ponto de controle.';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $top = $this->metrics()->topNonConformingCheckpoints();

        return [
            'datasets' => [[
                'label' => 'Não conformidades',
                'data' => $top->pluck('total')->all(),
                'backgroundColor' => '#dc2626',
            ]],
            'labels' => $top->pluck('label')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): RawJs
    {
        // Barras horizontais: nome de ponto não cabe na vertical.
        return RawJs::make(<<<'JS'
            {
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { ticks: { precision: 0 } } },
            }
        JS);
    }
}
