<?php

namespace App\Filament\Resources\ChecklistTemplates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ChecklistTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('unit_id')
                    ->relationship('unit', 'name'),
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Toggle::make('active')
                    ->required(),
            ]);
    }
}
