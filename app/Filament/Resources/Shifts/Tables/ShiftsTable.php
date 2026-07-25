<?php

namespace App\Filament\Resources\Shifts\Tables;

use App\Models\Shift;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('started_at')
                    ->label('Assunção')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('securityGuard.user.name')
                    ->label('Vigilante')
                    ->description(fn (Shift $record) => $record->securityGuard->registration)
                    ->searchable(),
                TextColumn::make('unit.name')
                    ->label('Unidade')
                    ->sortable(),
                TextColumn::make('post.name')
                    ->label('Posto'),
                TextColumn::make('duration')
                    ->label('Duração')
                    ->state(fn (Shift $record) => $record->ended_at
                        ? round($record->durationMinutes() / 60, 1).' h'
                        : 'em curso')
                    ->color(fn (Shift $record) => $record->ended_at ? null : 'warning'),
                TextColumn::make('patrols_count')
                    ->label('Rondas')
                    ->counts('patrols')
                    ->alignCenter(),
                TextColumn::make('incidents_count')
                    ->label('Ocorrências')
                    ->counts('incidents')
                    ->alignCenter(),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'open' ? 'Aberto' : 'Encerrado')
                    ->color(fn (string $state) => $state === 'open' ? 'info' : 'success'),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                SelectFilter::make('unit')
                    ->label('Unidade')
                    ->relationship('unit', 'name'),
                Filter::make('open')
                    ->label('Turnos abertos')
                    ->query(fn (Builder $query) => $query->where('status', 'open')),
            ])
            ->recordActions([
                EditAction::make()->label('Detalhes'),
            ]);
    }
}
