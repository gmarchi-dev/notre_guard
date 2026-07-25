<?php

namespace App\Filament\Resources\SecurityGuards\Tables;

use App\Models\SecurityGuard;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SecurityGuardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('registration')
                    ->label('Matrícula')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('defaultUnit.name')
                    ->label('Unidade')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('professional_id')
                    ->label('Registro prof.')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('refresher_valid_until')
                    ->label('Reciclagem')
                    ->date('d/m/Y')
                    ->placeholder('não informada')
                    ->badge()
                    ->color(fn (SecurityGuard $record) => $record->refresherExpired() ? 'danger' : 'success'),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->defaultSort('registration')
            ->filters([
                SelectFilter::make('defaultUnit')
                    ->label('Unidade')
                    ->relationship('defaultUnit', 'name'),
                TernaryFilter::make('active')->label('Ativo'),
                Filter::make('refresher_expired')
                    ->label('Reciclagem vencida')
                    ->query(fn (Builder $query) => $query->whereDate('refresher_valid_until', '<', now())),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
