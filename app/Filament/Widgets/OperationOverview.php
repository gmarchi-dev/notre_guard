<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsDashboardFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationOverview extends StatsOverviewWidget
{
    use ReadsDashboardFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $metrics = $this->metrics();

        $adherence = $metrics->adherence();
        $patrols = $metrics->patrolCounts();
        $incidents = $metrics->incidentCounts();
        $deviationRate = $metrics->scanDeviationRate();
        $openShifts = $metrics->openShifts();

        return [
            Stat::make('Aderência de ronda', $adherence === null ? '—' : "{$adherence}%")
                ->description($adherence === null
                    ? 'sem rondas no período'
                    : "meta 95% · {$this->periodLabel()}")
                ->descriptionIcon($adherence !== null && $adherence >= 95
                    ? 'heroicon-m-check-circle'
                    : 'heroicon-m-exclamation-triangle')
                ->color(match (true) {
                    $adherence === null => 'gray',
                    $adherence >= 95 => 'success',
                    $adherence >= 80 => 'warning',
                    default => 'danger',
                }),

            Stat::make('Rondas realizadas', (string) $patrols['total'])
                ->description("{$patrols['incomplete']} incompleta(s)")
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($patrols['incomplete'] > 0 ? 'warning' : 'success'),

            Stat::make('Ocorrências', (string) $incidents['total'])
                ->description("{$incidents['open']} em aberto · {$incidents['critical']} grave(s)")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($incidents['critical'] > 0 ? 'danger' : 'gray'),

            Stat::make('Leituras com desvio', $deviationRate === null ? '—' : "{$deviationRate}%")
                ->description('fora do raio, sem GPS, fora da janela ou fora de ordem')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color(match (true) {
                    $deviationRate === null => 'gray',
                    $deviationRate <= 5 => 'success',
                    $deviationRate <= 20 => 'warning',
                    default => 'danger',
                }),

            Stat::make('Turnos abertos agora', (string) $openShifts)
                ->description($openShifts === 0 ? 'nenhum posto assumido' : 'em serviço neste momento')
                ->descriptionIcon('heroicon-m-clock')
                ->color($openShifts > 0 ? 'info' : 'gray'),
        ];
    }
}
