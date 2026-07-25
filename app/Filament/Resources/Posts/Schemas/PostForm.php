<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('unit_id')
                    ->relationship('unit', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('kind')
                    ->required()
                    ->default('fixed'),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                Toggle::make('active')
                    ->required(),
            ]);
    }
}
