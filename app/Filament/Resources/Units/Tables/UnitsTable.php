<?php

namespace App\Filament\Resources\Units\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Sigla')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Unidade')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('posts_count')
                    ->label('Postos')
                    ->counts('posts')
                    ->alignCenter(),
                TextColumn::make('checkpoints_count')
                    ->label('Pontos')
                    ->counts('checkpoints')
                    ->alignCenter(),
                TextColumn::make('patrol_routes_count')
                    ->label('Roteiros')
                    ->counts('patrolRoutes')
                    ->alignCenter(),
                IconColumn::make('active')
                    ->label('Ativa')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('active')->label('Ativa'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
