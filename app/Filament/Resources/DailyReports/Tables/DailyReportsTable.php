<?php

namespace App\Filament\Resources\DailyReports\Tables;

use App\Models\DailyReport;
use App\Services\DailyReportBuilder;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use RuntimeException;

class DailyReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('report_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('unit.name')
                    ->label('Unidade')
                    ->sortable(),
                TextColumn::make('patrols')
                    ->label('Rondas')
                    ->state(fn (DailyReport $record) => sprintf(
                        '%d (%s incompleta%s)',
                        $record->summary['patrols']['total'] ?? 0,
                        $record->summary['patrols']['incomplete'] ?? 0,
                        ($record->summary['patrols']['incomplete'] ?? 0) === 1 ? '' : 's',
                    )),
                TextColumn::make('adherence')
                    ->label('Aderência')
                    ->state(fn (DailyReport $record) => $record->summary['patrols']['adherence'] !== null
                        ? $record->summary['patrols']['adherence'].'%'
                        : '—')
                    ->badge()
                    ->color(fn (DailyReport $record) => match (true) {
                        ($record->summary['patrols']['adherence'] ?? null) === null => 'gray',
                        $record->summary['patrols']['adherence'] >= 95 => 'success',
                        $record->summary['patrols']['adherence'] >= 80 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('incidents')
                    ->label('Ocorrências')
                    ->state(fn (DailyReport $record) => $record->summary['incidents']['total'] ?? 0)
                    ->alignCenter(),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => DailyReport::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'closed' ? 'success' : 'warning'),
            ])
            ->defaultSort('report_date', 'desc')
            ->filters([
                SelectFilter::make('unit')
                    ->label('Unidade')
                    ->relationship('unit', 'name'),
                SelectFilter::make('status')
                    ->label('Situação')
                    ->options(DailyReport::STATUSES),
            ])
            ->recordActions([
                EditAction::make()->label('Abrir'),
                Action::make('close')
                    ->label('Fechar')
                    ->icon('heroicon-o-lock-closed')
                    ->color('success')
                    ->visible(fn (DailyReport $record) => ! $record->isClosed())
                    ->requiresConfirmation()
                    ->modalHeading('Fechar o RDO')
                    ->modalDescription('Depois de fechado o conteúdo é selado e o PDF é gerado. Registros que chegarem depois ficam sinalizados.')
                    ->action(function (DailyReport $record, DailyReportBuilder $builder) {
                        try {
                            $builder->close($record, auth()->user());
                        } catch (RuntimeException $e) {
                            Notification::make()
                                ->title('Não foi possível fechar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()->title('RDO fechado.')->success()->send();
                    }),
                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (DailyReport $record) => filled($record->pdf_path))
                    ->url(fn (DailyReport $record) => route('rdo.pdf', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
