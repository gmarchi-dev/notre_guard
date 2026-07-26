<?php

namespace App\Filament\Portaria\Resources\KeyLoans\Tables;

use App\Models\KeyLoan;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * O livro da portaria: histórico de quem levou o quê, quando e se devolveu.
 * Somente leitura — retirada e devolução acontecem no quadro de chaves.
 */
class KeyLoansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['keyItem', 'holder', 'releasedBy', 'receivedBy']))
            ->columns([
                TextColumn::make('released_at')
                    ->label('Retirada')
                    ->dateTime('d/m/Y H:i')
                    ->description(fn (KeyLoan $record) => 'por '.($record->releasedBy?->name ?? '—'))
                    ->sortable(),

                TextColumn::make('keyItem.code')
                    ->label('Chave')
                    ->description(fn (KeyLoan $record) => $record->keyItem?->name)
                    ->searchable(),

                TextColumn::make('holder.name')
                    ->label('Solicitante')
                    ->description(fn (KeyLoan $record) => $record->holder?->department)
                    ->searchable(),

                TextColumn::make('purpose')
                    ->label('Motivo')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('due_at')
                    ->label('Prazo')
                    ->dateTime('d/m H:i'),

                TextColumn::make('returned_at')
                    ->label('Devolução')
                    ->state(fn (KeyLoan $record) => $record->returned_at?->format('d/m/Y H:i') ?? 'em aberto')
                    ->description(fn (KeyLoan $record) => match (true) {
                        $record->isOverdue() => 'atrasada há '.$record->overdueMinutes().' min',
                        $record->returned_at && $record->overdueMinutes() > 0 => 'devolvida com '.$record->overdueMinutes().' min de atraso',
                        $record->returned_at => 'no prazo',
                        default => null,
                    })
                    ->badge()
                    ->color(fn (KeyLoan $record) => match (true) {
                        $record->isOverdue() => 'danger',
                        ! $record->returned_at => 'warning',
                        $record->overdueMinutes() > 0 => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('notes')
                    ->label('Observação')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('released_at', 'desc')
            ->filters([
                Filter::make('open')
                    ->label('Somente em aberto')
                    ->query(fn (Builder $query) => $query->open()),
                Filter::make('overdue')
                    ->label('Somente atrasadas')
                    ->query(fn (Builder $query) => $query->overdue()),
                SelectFilter::make('unit')
                    ->label('Unidade')
                    ->relationship('unit', 'name'),
                SelectFilter::make('holder')
                    ->label('Solicitante')
                    ->relationship('holder', 'name')
                    ->searchable(),
            ])
            ->emptyStateHeading('Nenhuma retirada registrada');
    }
}
