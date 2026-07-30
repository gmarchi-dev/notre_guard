<?php

namespace App\Filament\Resources\Incidents\Schemas;

use App\Models\Incident;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Ocorrência: os fatos relatados em campo são imutáveis; o que a supervisão
 * acrescenta é a análise. Editar o relato do vigilante depois do registro
 * destruiria o valor probatório do RDO.
 */
class IncidentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Registro do vigilante')
                ->description('Somente leitura - conteúdo original enviado do campo.')
                ->columns(3)
                ->schema([
                    TextEntry::make('number')->label('Número')->weight('bold'),
                    TextEntry::make('unit.name')->label('Unidade'),
                    TextEntry::make('reportedBy.user.name')->label('Registrada por')->placeholder('—'),

                    TextEntry::make('occurred_at')
                        ->label('Hora do fato')
                        ->dateTime('d/m/Y H:i'),
                    TextEntry::make('received_at')
                        ->label('Hora do registro')
                        ->dateTime('d/m/Y H:i')
                        ->helperText('Diferença grande indica registro feito sem rede.'),
                    TextEntry::make('location')->label('Local')->placeholder('—'),

                    TextEntry::make('type.name')
                        ->label('Tipo')
                        ->state(fn (Incident $record) => $record->type->fullName()),
                    TextEntry::make('severity')
                        ->label('Gravidade')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => Incident::SEVERITIES[$state] ?? $state),
                    TextEntry::make('classification')
                        ->label('Classificação')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => Incident::CLASSIFICATIONS[$state] ?? $state),

                    TextEntry::make('description')
                        ->label('Relato')
                        ->columnSpanFull(),
                    TextEntry::make('actions_taken')
                        ->label('Providências tomadas')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),

            Section::make('Análise da supervisão')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('Situação')
                        ->options(Incident::STATUSES)
                        ->required(),
                    Textarea::make('review_notes')
                        ->label('Parecer')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
