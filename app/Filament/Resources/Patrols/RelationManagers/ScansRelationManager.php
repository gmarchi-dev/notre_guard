<?php

namespace App\Filament\Resources\Patrols\RelationManagers;

use App\Models\PatrolScan;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScansRelationManager extends RelationManager
{
    protected static string $relationship = 'scans';

    protected static ?string $title = 'Leituras';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('uuid')
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Hora')
                    ->dateTime('H:i:s')
                    ->description(fn (PatrolScan $record) => 'recebido '.$record->received_at->format('H:i:s'))
                    ->sortable(),
                TextColumn::make('checkpoint.code')
                    ->label('Ponto')
                    ->description(fn (PatrolScan $record) => $record->checkpoint->name),
                TextColumn::make('method')
                    ->label('Leitura')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'qr' => 'QR Code',
                        'nfc' => 'NFC',
                        default => 'Manual',
                    })
                    ->color(fn (string $state) => $state === 'manual' ? 'warning' : 'gray'),
                TextColumn::make('distance_m')
                    ->label('Distância')
                    ->formatStateUsing(fn (?int $state) => $state === null ? '—' : "{$state} m")
                    ->color(fn (PatrolScan $record) => in_array(PatrolScan::DEVIATION_OUT_OF_RADIUS, $record->deviations ?? [], true) ? 'danger' : null),
                TextColumn::make('deviations')
                    ->label('Desvios')
                    ->badge()
                    ->state(fn (PatrolScan $record) => $record->deviationLabels() ?: ['Sem desvio'])
                    ->color(fn (PatrolScan $record) => $record->hasDeviations() ? 'danger' : 'success'),
                TextColumn::make('justification')
                    ->label('Justificativa')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
            ])
            ->defaultSort('occurred_at')
            ->paginated(false);
    }
}
