<?php

namespace App\Filament\Resources\Patrols\Schemas;

use App\Models\Patrol;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Ficha da ronda - somente leitura.
 *
 * Ronda é registro de campo: imutável. Se o painel permitisse editar, a
 * aderência de ronda viraria um número negociável.
 */
class PatrolForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ronda')
                ->columns(3)
                ->schema([
                    TextEntry::make('patrolRoute.name')->label('Roteiro'),
                    TextEntry::make('unit.name')->label('Unidade'),
                    TextEntry::make('shift.securityGuard.user.name')->label('Vigilante'),

                    TextEntry::make('started_at')
                        ->label('Início')
                        ->dateTime('d/m/Y H:i:s'),
                    TextEntry::make('ended_at')
                        ->label('Encerramento')
                        ->dateTime('d/m/Y H:i:s')
                        ->placeholder('em curso'),
                    TextEntry::make('duration')
                        ->label('Duração')
                        ->state(fn (Patrol $record) => $record->ended_at
                            ? round($record->ended_at->diffInMinutes($record->started_at)).' min (previsto: '.$record->patrolRoute->expected_duration_min.' min)'
                            : '—'),

                    TextEntry::make('completion')
                        ->label('Aderência')
                        ->state(fn (Patrol $record) => "{$record->scanned_checkpoints} de {$record->expected_checkpoints} pontos ({$record->completionRate()}%)")
                        ->badge()
                        ->color(fn (Patrol $record) => $record->scanned_checkpoints >= $record->expected_checkpoints ? 'success' : 'danger'),
                    TextEntry::make('status')
                        ->label('Situação')
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'in_progress' => 'Em andamento',
                            'completed' => 'Concluída',
                            default => 'Abandonada',
                        }),
                    TextEntry::make('deviations')
                        ->label('Desvios da ronda')
                        ->badge()
                        ->color('danger')
                        ->placeholder('nenhum')
                        ->formatStateUsing(fn (string $state) => $state === 'incomplete' ? 'Roteiro incompleto' : $state),
                ]),

            Section::make('Rastreabilidade')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextEntry::make('uuid')
                        ->label('UUID do dispositivo')
                        ->copyable(),
                    TextEntry::make('started_received_at')
                        ->label('Recebido pelo servidor')
                        ->dateTime('d/m/Y H:i:s')
                        ->helperText('Diferença grande para o início indica registro feito sem rede.'),
                ]),
        ]);
    }
}
