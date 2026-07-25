<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsDashboardFilters;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class IncidentsByTypeChart extends ChartWidget
{
    use ReadsDashboardFilters;

    protected ?string $heading = 'Ocorrências por tipo';

    protected ?string $description = 'Tipos mais frequentes no período.';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $types = $this->metrics()->incidentsByType();

        return [
            'datasets' => [[
                'label' => 'Ocorrências',
                'data' => $types->pluck('total')->all(),
                'backgroundColor' => '#2563eb',
            ]],
            'labels' => $types->pluck('label')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { ticks: { precision: 0 } } },
            }
        JS);
    }
}
