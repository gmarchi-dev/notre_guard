<?php

namespace App\Filament\Resources\DailyReports\Pages;

use App\Filament\Resources\DailyReports\DailyReportResource;
use App\Models\DailyReport;
use App\Services\DailyReportBuilder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use RuntimeException;

class EditDailyReport extends EditRecord
{
    protected static string $resource = DailyReportResource::class;

    protected static ?string $title = 'RDO';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var DailyReport $report */
        $report = $this->record;

        if (! $report->isClosed()) {
            // Rascunho reflete o banco no momento em que é aberto.
            app(DailyReportBuilder::class)->buildOrUpdate($report->unit, $report->report_date);
            $this->record = $report->refresh();
            $this->fillForm();

            return;
        }

        if (app(DailyReportBuilder::class)->hasLateRecords($report)) {
            // Aparelho que ficou dias sem rede faz registros chegarem depois do
            // fechamento. O documento continua válido, mas deixou de refletir o
            // que existe hoje — e quem lê precisa saber disso.
            Notification::make()
                ->title('Registros chegaram após o fechamento')
                ->body('O conteúdo atual desta data não bate mais com o selo do RDO fechado. Considere reabrir e fechar de novo.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('close')
                ->label('Fechar RDO')
                ->icon('heroicon-o-lock-closed')
                ->color('success')
                ->visible(fn (DailyReport $record) => ! $record->isClosed())
                ->requiresConfirmation()
                ->modalDescription('O conteúdo é selado por hash e o PDF é gerado.')
                ->action(function (DailyReport $record, DailyReportBuilder $builder) {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

                    try {
                        $builder->close($record->refresh(), auth()->user());
                    } catch (RuntimeException $e) {
                        Notification::make()
                            ->title('Não foi possível fechar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()->title('RDO fechado.')->success()->send();
                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $record]));
                }),

            Action::make('reopen')
                ->label('Reabrir')
                ->icon('heroicon-o-lock-open')
                ->color('warning')
                ->visible(fn (DailyReport $record) => $record->isClosed() && auth()->user()->isAdmin())
                ->requiresConfirmation()
                ->modalDescription('Reabrir invalida o selo e o PDF atual. Use quando registros chegaram depois do fechamento.')
                ->action(function (DailyReport $record) {
                    $record->update([
                        'status' => 'draft',
                        'closed_at' => null,
                        'closed_by_user_id' => null,
                        'content_hash' => null,
                        'pdf_path' => null,
                    ]);

                    Notification::make()->title('RDO reaberto.')->success()->send();
                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $record]));
                }),

            Action::make('pdf')
                ->label('Baixar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (DailyReport $record) => filled($record->pdf_path))
                ->url(fn (DailyReport $record) => route('rdo.pdf', $record))
                ->openUrlInNewTab(),
        ];
    }

    /** Só as observações da supervisão são editáveis; o resto é agregação. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ['notes' => $data['notes'] ?? null];
    }

    protected function getFormActions(): array
    {
        return $this->record->isClosed() ? [] : parent::getFormActions();
    }
}
