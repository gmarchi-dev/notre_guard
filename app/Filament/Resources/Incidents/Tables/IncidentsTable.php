<?php

namespace App\Filament\Resources\Incidents\Tables;

use App\Models\Incident;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IncidentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Número')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('occurred_at')
                    ->label('Fato')
                    ->dateTime('d/m/Y H:i')
                    ->description(fn (Incident $record) => 'registro '.$record->received_at->format('d/m H:i'))
                    ->sortable(),
                TextColumn::make('unit.name')
                    ->label('Unidade')
                    ->sortable(),
                TextColumn::make('type.name')
                    ->label('Tipo')
                    ->description(fn (Incident $record) => $record->type->parent?->name)
                    ->searchable(),
                TextColumn::make('severity')
                    ->label('Gravidade')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Incident::SEVERITIES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('classification')
                    ->label('Classificação')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Incident::CLASSIFICATIONS[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'loss' ? 'danger' : 'success'),
                TextColumn::make('reportedBy.user.name')
                    ->label('Registrada por')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Incident::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'closed' => 'success',
                        'under_review' => 'warning',
                        default => 'info',
                    }),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->filters([
                SelectFilter::make('unit')
                    ->label('Unidade')
                    ->relationship('unit', 'name'),
                SelectFilter::make('severity')
                    ->label('Gravidade')
                    ->options(Incident::SEVERITIES),
                SelectFilter::make('status')
                    ->label('Situação')
                    ->options(Incident::STATUSES),
                SelectFilter::make('classification')
                    ->label('Classificação')
                    ->options(Incident::CLASSIFICATIONS),
            ])
            ->recordActions([
                EditAction::make()->label('Analisar'),
            ]);
    }
}
