<?php

namespace App\Filament\Resources\Incidents;

use App\Filament\Concerns\ScopedToUnit;
use App\Filament\Resources\Incidents\Pages\EditIncident;
use App\Filament\Resources\Incidents\Pages\ListIncidents;
use App\Filament\Resources\Incidents\Schemas\IncidentForm;
use App\Filament\Resources\Incidents\Tables\IncidentsTable;
use App\Models\Incident;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IncidentResource extends Resource
{
    use ScopedToUnit;

    protected static ?string $model = Incident::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Operação';

    protected static ?string $modelLabel = 'ocorrência';

    protected static ?string $pluralModelLabel = 'ocorrências';

    protected static ?int $navigationSort = 1;

    /**
     * Ocorrência nasce no aplicativo do vigilante, como turno e ronda. A ficha
     * aqui mostra o relato original e recebe a análise da supervisão - não há
     * como criar uma da qual ninguém foi testemunha.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return IncidentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IncidentsTable::configure($table);
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
            'index' => ListIncidents::route('/'),
            'edit' => EditIncident::route('/{record}/edit'),
        ];
    }
}
