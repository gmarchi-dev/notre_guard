<?php

namespace App\Filament\Resources\IncidentTypes;

use App\Filament\Resources\IncidentTypes\Pages\CreateIncidentType;
use App\Filament\Resources\IncidentTypes\Pages\EditIncidentType;
use App\Filament\Resources\IncidentTypes\Pages\ListIncidentTypes;
use App\Filament\Resources\IncidentTypes\Schemas\IncidentTypeForm;
use App\Filament\Resources\IncidentTypes\Tables\IncidentTypesTable;
use App\Models\IncidentType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IncidentTypeResource extends Resource
{
    protected static ?string $model = IncidentType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Configuração';

    protected static ?string $modelLabel = 'tipo de ocorrência';

    protected static ?string $pluralModelLabel = 'tipos de ocorrência';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return IncidentTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IncidentTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIncidentTypes::route('/'),
            'create' => CreateIncidentType::route('/create'),
            'edit' => EditIncidentType::route('/{record}/edit'),
        ];
    }
}
