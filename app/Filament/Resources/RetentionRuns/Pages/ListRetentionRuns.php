<?php

namespace App\Filament\Resources\RetentionRuns\Pages;

use App\Filament\Resources\RetentionRuns\RetentionRunResource;
use App\Services\RetentionPurger;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListRetentionRuns extends ListRecords
{
    protected static string $resource = RetentionRunResource::class;

    protected static ?string $title = 'Retenção de dados';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dryRun')
                ->label('Simular expurgo')
                ->icon('heroicon-o-magnifying-glass')
                ->color('gray')
                // Simulação não apaga nada, então não pede confirmação. É como
                // se confere a política antes de confiar no agendamento.
                ->action(function (RetentionPurger $purger) {
                    $run = $purger->run(dryRun: true);

                    Notification::make()
                        ->title('Simulação concluída')
                        ->body($run->totalRemoved() === 0
                            ? 'Nada vencido no momento.'
                            : "{$run->totalRemoved()} registro(s) seriam eliminados.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
