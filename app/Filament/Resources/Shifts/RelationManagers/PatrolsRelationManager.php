<?php

namespace App\Filament\Resources\Shifts\RelationManagers;

use App\Filament\Resources\Patrols\PatrolResource;
use App\Models\Patrol;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PatrolsRelationManager extends RelationManager
{
    protected static string $relationship = 'patrols';

    protected static ?string $title = 'Rondas do turno';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('uuid')
            ->columns([
                TextColumn::make('started_at')
                    ->label('Início')
                    ->dateTime('H:i'),
                TextColumn::make('patrolRoute.name')
                    ->label('Roteiro'),
                TextColumn::make('completion')
                    ->label('Pontos')
                    ->state(fn (Patrol $record) => "{$record->scanned_checkpoints}/{$record->expected_checkpoints}")
                    ->badge()
                    ->color(fn (Patrol $record) => $record->scanned_checkpoints >= $record->expected_checkpoints ? 'success' : 'danger'),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'in_progress' => 'Em andamento',
                        'completed' => 'Concluída',
                        default => 'Abandonada',
                    }),
            ])
            ->defaultSort('started_at')
            ->paginated(false)
            ->recordActions([
                Action::make('open')
                    ->label('Abrir')
                    ->url(fn (Patrol $record) => PatrolResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
