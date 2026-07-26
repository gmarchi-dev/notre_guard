<?php

namespace App\Filament\Resources\SafetyAlerts\Tables;

use App\Models\SafetyAlert;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SafetyAlertsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kind')
                    ->label('Alerta')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => SafetyAlert::KINDS[$state] ?? $state)
                    ->color(fn (string $state) => $state === SafetyAlert::KIND_PANIC ? 'danger' : 'warning')
                    ->icon(fn (string $state) => $state === SafetyAlert::KIND_PANIC
                        ? 'heroicon-m-bell-alert'
                        : 'heroicon-m-clock'),

                TextColumn::make('occurred_at')
                    ->label('Acionado')
                    ->dateTime('d/m/Y H:i:s')
                    ->description(fn (SafetyAlert $record) => $record->received_at->diffInSeconds($record->occurred_at) > 60
                        ? 'recebido '.$record->received_at->format('H:i:s').' (fora de rede)'
                        : null)
                    ->sortable(),

                TextColumn::make('securityGuard.user.name')
                    ->label('Vigilante')
                    ->description(fn (SafetyAlert $record) => $record->securityGuard?->registration)
                    ->searchable(),

                TextColumn::make('unit.name')
                    ->label('Unidade')
                    ->placeholder('—'),

                TextColumn::make('detail')
                    ->label('Detalhe')
                    ->state(fn (SafetyAlert $record) => $record->kind === SafetyAlert::KIND_INACTIVITY
                        ? $record->silence_minutes.' min sem registro'
                        : ($record->patrol_id ? 'durante ronda' : 'em posto'))
                    ->color('gray'),

                TextColumn::make('location')
                    ->label('Localização')
                    ->state(fn (SafetyAlert $record) => $record->latitude === null
                        ? 'sem GPS'
                        : $record->latitude.', '.$record->longitude)
                    ->url(fn (SafetyAlert $record) => $record->latitude === null
                        ? null
                        : "https://maps.google.com/?q={$record->latitude},{$record->longitude}")
                    ->openUrlInNewTab()
                    ->color(fn (SafetyAlert $record) => $record->latitude === null ? 'gray' : 'primary'),

                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => SafetyAlert::STATUSES[$state] ?? $state)
                    ->description(fn (SafetyAlert $record) => $record->minutesToAcknowledge() !== null
                        ? 'atendido em '.$record->minutesToAcknowledge().' min'
                        : null)
                    ->color(fn (string $state) => match ($state) {
                        'open' => 'danger',
                        'acknowledged' => 'warning',
                        'false_alarm' => 'gray',
                        default => 'success',
                    }),
            ])
            ->defaultSort('occurred_at', 'desc')
            // Alerta aberto vem sempre primeiro, seja de quando for.
            ->modifyQueryUsing(fn (Builder $query) => $query->orderByRaw("status = 'open' DESC"))
            ->filters([
                Filter::make('open')
                    ->label('Somente em aberto')
                    ->default()
                    ->query(fn (Builder $query) => $query->where('status', 'open')),
                SelectFilter::make('kind')
                    ->label('Tipo')
                    ->options(SafetyAlert::KINDS),
                SelectFilter::make('unit')
                    ->label('Unidade')
                    ->relationship('unit', 'name'),
            ])
            ->recordActions([
                Action::make('acknowledge')
                    ->label('Reconhecer')
                    ->icon('heroicon-o-hand-raised')
                    ->color('warning')
                    ->visible(fn (SafetyAlert $record) => $record->isOpen())
                    // Sem confirmação: reconhecer é dizer "estou vendo", e
                    // qualquer clique a mais aqui custa segundos que importam.
                    ->action(function (SafetyAlert $record) {
                        $record->update([
                            'status' => 'acknowledged',
                            'acknowledged_by_user_id' => auth()->id(),
                            'acknowledged_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Alerta reconhecido')
                            ->body('Confirme o atendimento por rádio ou telefone.')
                            ->warning()
                            ->send();
                    }),

                Action::make('resolve')
                    ->label('Encerrar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (SafetyAlert $record) => $record->status !== 'resolved' && $record->status !== 'false_alarm')
                    ->schema([
                        Textarea::make('notes')
                            ->label('O que aconteceu')
                            ->helperText('Fica no histórico do alerta. Seja específico: é o que se lê depois.')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (SafetyAlert $record, array $data) {
                        $record->update([
                            'status' => 'resolved',
                            'resolved_at' => now(),
                            'notes' => $data['notes'],
                            'acknowledged_by_user_id' => $record->acknowledged_by_user_id ?? auth()->id(),
                            'acknowledged_at' => $record->acknowledged_at ?? now(),
                        ]);

                        Notification::make()->title('Alerta encerrado.')->success()->send();
                    }),

                Action::make('falseAlarm')
                    ->label('Falso alarme')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn (SafetyAlert $record) => $record->status !== 'resolved' && $record->status !== 'false_alarm')
                    ->requiresConfirmation()
                    ->modalHeading('Marcar como falso alarme')
                    ->modalDescription('Use somente após confirmar com o vigilante que não houve emergência.')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Como foi confirmado')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function (SafetyAlert $record, array $data) {
                        $record->update([
                            'status' => 'false_alarm',
                            'resolved_at' => now(),
                            'notes' => $data['notes'],
                            'acknowledged_by_user_id' => $record->acknowledged_by_user_id ?? auth()->id(),
                            'acknowledged_at' => $record->acknowledged_at ?? now(),
                        ]);
                    }),
            ])
            ->emptyStateHeading('Nenhum alerta')
            ->emptyStateDescription('Botão de pânico e inatividade em ronda aparecem aqui.')
            // A tela é de plantão: recarrega sozinha.
            ->poll('30s');
    }
}
