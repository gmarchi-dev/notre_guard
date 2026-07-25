<?php

namespace App\Filament\Resources\SecurityGuards\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SecurityGuardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('default_unit_id')
                    ->relationship('defaultUnit', 'name'),
                TextInput::make('registration')
                    ->required(),
                TextInput::make('professional_id'),
                DatePicker::make('refresher_valid_until'),
                TextInput::make('phone')
                    ->tel(),
                Toggle::make('active')
                    ->required(),
            ]);
    }
}
