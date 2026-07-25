<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsDashboardFilters;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AdherenceChart extends ChartWidget
{
    use ReadsDashboardFilters;

    protected ?string $heading = 'Aderência de ronda por dia';

    protected ?string $description = 'Pontos lidos ÷ pontos previstos. Dias sem ronda ficam vazios.';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $series = $this->metrics()->adherenceByDay();

        return [
            'datasets' => [
                [
                    'label' => 'Aderência (%)',
                    'data' => array_values($series),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                    // Sem isso o Chart.js liga os pontos por cima dos dias sem
                    // operação, sugerindo uma ronda que não existiu.
                    'spanGaps' => false,
                ],
                [
                    'label' => 'Meta (95%)',
                    'data' => array_fill(0, count($series), 95),
                    'borderColor' => '#16a34a',
                    'borderDash' => [6, 4],
                    'pointRadius' => 0,
                    'fill' => false,
                ],
            ],
            'labels' => array_map(
                fn (string $day) => Carbon::parse($day)->format('d/m'),
                array_keys($series),
            ),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                scales: {
                    y: { min: 0, max: 100, ticks: { callback: (v) => v + '%' } },
                },
                plugins: { legend: { display: true } },
            }
        JS);
    }
}
