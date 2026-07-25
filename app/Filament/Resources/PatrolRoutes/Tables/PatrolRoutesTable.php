<?php

namespace App\Filament\Resources\PatrolRoutes\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PatrolRoutesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unit.name')
                    ->label('Unidade')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Roteiro')
                    ->searchable(),
                TextColumn::make('checkpoints_count')
                    ->label('Pontos')
                    ->counts('checkpoints')
                    ->alignCenter(),
                TextColumn::make('expected_duration_min')
                    ->label('Previsto')
                    ->suffix(' min')
                    ->alignCenter(),
                IconColumn::make('ordered')
                    ->label('Ordenado')
                    ->boolean(),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('unit')
                    ->label('Unidade')
                    ->relationship('unit', 'name'),
                TernaryFilter::make('active')->label('Ativo'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
