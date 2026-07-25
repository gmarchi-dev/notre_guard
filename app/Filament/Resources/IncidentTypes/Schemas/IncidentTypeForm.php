<?php

namespace App\Filament\Resources\IncidentTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class IncidentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->relationship('parent', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('default_classification'),
                TextInput::make('default_severity'),
                Toggle::make('notify_supervision')
                    ->required(),
                Toggle::make('active')
                    ->required(),
            ]);
    }
}
