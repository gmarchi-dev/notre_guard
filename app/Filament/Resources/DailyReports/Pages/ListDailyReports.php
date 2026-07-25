<?php

namespace App\Filament\Resources\DailyReports\Pages;

use App\Filament\Resources\DailyReports\DailyReportResource;
use App\Models\DailyReport;
use App\Models\Unit;
use App\Services\DailyReportBuilder;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;

class ListDailyReports extends ListRecords
{
    protected static string $resource = DailyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Gerar RDO')
                ->icon('heroicon-o-document-plus')
                ->schema([
                    Select::make('unit_id')
                        ->label('Unidade')
                        ->options(fn () => Unit::query()
                            ->where('active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->default(fn () => auth()->user()->unit_id)
                        ->searchable()
                        ->required(),
                    DatePicker::make('report_date')
                        ->label('Data')
                        ->default(now()->subDay())
                        ->maxDate(now())
                        ->required()
                        ->helperText('O RDO costuma ser fechado no dia seguinte, depois de encerrados os turnos.'),
                ])
                ->action(function (array $data, DailyReportBuilder $builder) {
                    $unit = Unit::findOrFail($data['unit_id']);
                    $date = Carbon::parse($data['report_date']);

                    $report = $builder->buildOrUpdate($unit, $date);

                    Notification::make()
                        ->title($report->wasRecentlyCreated ? 'RDO gerado.' : 'RDO atualizado.')
                        ->body($report->isClosed()
                            ? 'Este RDO já está fechado e não foi recalculado.'
                            : "{$report->summary['patrols']['total']} ronda(s) e {$report->summary['incidents']['total']} ocorrência(s) nesta data.")
                        ->success()
                        ->send();

                    $this->redirect(DailyReportResource::getUrl('edit', ['record' => $report]));
                }),
        ];
    }

    /**
     * Rascunho é espelho do banco, não fotografia: recalcula ao abrir a
     * listagem, senão os números envelhecem sem ninguém perceber. Limitado aos
     * últimos 30 dias para a tela não ficar cara com o passar dos meses.
     */
    public function mount(): void
    {
        parent::mount();

        $builder = app(DailyReportBuilder::class);

        DailyReport::query()
            ->where('status', 'draft')
            ->where('report_date', '>=', now()->subDays(30)->toDateString())
            ->with('unit')
            ->get()
            ->each(fn (DailyReport $report) => $builder->buildOrUpdate($report->unit, $report->report_date));
    }
}
