<?php

namespace App\Filament\Resources\Patrols\Tables;

use App\Models\Patrol;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PatrolsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('started_at')
                    ->label('Início')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('unit.name')
                    ->label('Unidade')
                    ->sortable(),
                TextColumn::make('patrolRoute.name')
                    ->label('Roteiro')
                    ->searchable(),
                TextColumn::make('shift.securityGuard.user.name')
                    ->label('Vigilante')
                    ->searchable(),
                TextColumn::make('completion')
                    ->label('Pontos')
                    ->state(fn (Patrol $record) => "{$record->scanned_checkpoints}/{$record->expected_checkpoints}")
                    ->badge()
                    ->color(fn (Patrol $record) => match (true) {
                        $record->expected_checkpoints === 0 => 'gray',
                        $record->scanned_checkpoints >= $record->expected_checkpoints => 'success',
                        $record->completionRate() >= 70 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('duration')
                    ->label('Duração')
                    ->state(fn (Patrol $record) => $record->ended_at
                        ? round($record->ended_at->diffInMinutes($record->started_at)).' min'
                        : 'em curso')
                    ->color(fn (Patrol $record) => $record->ended_at ? null : 'warning'),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'in_progress' => 'Em andamento',
                        'completed' => 'Concluída',
                        'abandoned' => 'Abandonada',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'completed' => 'success',
                        'abandoned' => 'danger',
                        default => 'info',
                    }),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                SelectFilter::make('unit')
                    ->label('Unidade')
                    ->relationship('unit', 'name'),
                Filter::make('incomplete')
                    ->label('Somente incompletas')
                    ->query(fn (Builder $query) => $query->whereColumn('scanned_checkpoints', '<', 'expected_checkpoints')),
                Filter::make('open')
                    ->label('Em andamento')
                    ->query(fn (Builder $query) => $query->where('status', 'in_progress')),
            ])
            ->recordActions([
                EditAction::make()->label('Detalhes'),
            ]);
    }
}
