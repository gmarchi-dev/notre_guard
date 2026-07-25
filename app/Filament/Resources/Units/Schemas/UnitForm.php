<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->label('Sigla')
                            ->helperText('Usada na numeração do RDO e das ocorrências.')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),
                        TextInput::make('address')
                            ->label('Endereço')
                            ->columnSpanFull()
                            ->maxLength(255),
                        Toggle::make('active')
                            ->label('Ativa')
                            ->default(true),
                    ]),

                Section::make('Localização')
                    ->description('Coordenada do centro da unidade. Serve de referência para validar as leituras de ronda.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->step(0.0000001)
                            ->placeholder('-22.9056'),
                        TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->step(0.0000001)
                            ->placeholder('-47.0608'),
                        TextInput::make('radius_m')
                            ->label('Raio (m)')
                            ->numeric()
                            ->minValue(10)
                            ->default(200)
                            ->required(),
                    ]),
            ]);
    }
}
