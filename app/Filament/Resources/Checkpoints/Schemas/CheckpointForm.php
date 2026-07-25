<?php

namespace App\Filament\Resources\Checkpoints\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CheckpointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('unit_id')
                    ->relationship('unit', 'name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('nfc_uid'),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                TextInput::make('radius_m')
                    ->required()
                    ->numeric()
                    ->default(50),
                Textarea::make('instruction')
                    ->columnSpanFull(),
                Select::make('checklist_template_id')
                    ->relationship('checklistTemplate', 'name'),
                Toggle::make('active')
                    ->required(),
            ]);
    }
}
