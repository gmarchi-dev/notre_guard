<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsDashboardFilters;
use Filament\Widgets\ChartWidget;

/**
 * Em que horas do dia as ocorrências acontecem. É o indicador que orienta
 * reforço de posto e redesenho de janela de ronda.
 */
class IncidentsByHourChart extends ChartWidget
{
    use ReadsDashboardFilters;

    protected ?string $heading = 'Ocorrências por hora do dia';

    protected ?string $description = 'Concentração por faixa horária no período.';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $counts = $this->metrics()->incidentsByHour();

        return [
            'datasets' => [[
                'label' => 'Ocorrências',
                'data' => array_values($counts),
                'backgroundColor' => '#d97706',
            ]],
            'labels' => array_map(
                fn (int $hour) => str_pad((string) $hour, 2, '0', STR_PAD_LEFT).'h',
                array_keys($counts),
            ),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
