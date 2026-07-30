<?php

namespace App\Filament\Resources\Shifts\Schemas;

use App\Models\Shift;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Ficha do turno - somente leitura. Ver PatrolForm para o porquê.
 */
class ShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Turno')
                ->columns(3)
                ->schema([
                    TextEntry::make('securityGuard.user.name')->label('Vigilante'),
                    TextEntry::make('unit.name')->label('Unidade'),
                    TextEntry::make('post.name')->label('Posto'),

                    TextEntry::make('started_at')
                        ->label('Assunção')
                        ->dateTime('d/m/Y H:i:s'),
                    TextEntry::make('ended_at')
                        ->label('Encerramento')
                        ->dateTime('d/m/Y H:i:s')
                        ->placeholder('em curso'),
                    TextEntry::make('duration')
                        ->label('Duração')
                        ->state(fn (Shift $record) => $record->ended_at
                            ? round($record->durationMinutes() / 60, 1).' h'
                            : '—'),

                    TextEntry::make('handover_notes')
                        ->label('Passagem de serviço')
                        ->placeholder('sem pendências registradas')
                        ->columnSpanFull(),
                ]),

            Section::make('Rastreabilidade')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextEntry::make('uuid')->label('UUID do dispositivo')->copyable(),
                    TextEntry::make('device_id')->label('Aparelho')->placeholder('—'),
                    TextEntry::make('chain_hash')
                        ->label('Selo de integridade')
                        ->placeholder('turno ainda aberto')
                        ->helperText('Encadeia os eventos do turno. Alterar qualquer registro depois do fechamento quebra o selo.')
                        ->copyable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
