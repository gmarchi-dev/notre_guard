<?php

namespace App\Filament\Resources\PatrolRoutes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatrolRouteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    Select::make('unit_id')
                        ->label('Unidade')
                        ->relationship('unit', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('name')
                        ->label('Nome do roteiro')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->label('Descrição')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Execução')
                ->columns(3)
                ->schema([
                    Toggle::make('ordered')
                        ->label('Seguir a ordem dos pontos')
                        ->helperText('Se desligado, o vigilante pode ler os pontos em qualquer sequência.')
                        ->default(true),
                    TextInput::make('expected_duration_min')
                        ->label('Duração prevista (min)')
                        ->numeric()
                        ->minValue(1)
                        ->default(30)
                        ->required(),
                    TextInput::make('tolerance_min')
                        ->label('Tolerância (min)')
                        ->numeric()
                        ->minValue(0)
                        ->default(15)
                        ->required(),
                    Toggle::make('active')
                        ->label('Ativo')
                        ->default(true),
                ]),
        ]);
    }
}
