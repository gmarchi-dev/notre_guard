<?php

namespace App\Filament\Resources\Shifts;

use App\Filament\Resources\Shifts\Pages\EditShift;
use App\Filament\Resources\Shifts\Pages\ListShifts;
use App\Filament\Resources\Shifts\Schemas\ShiftForm;
use App\Filament\Resources\Shifts\Tables\ShiftsTable;
use App\Models\Shift;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Operação';

    protected static ?string $modelLabel = 'turno';

    protected static ?string $pluralModelLabel = 'turnos';

    protected static ?int $navigationSort = 2;

    /** Turnos nascem em campo — o painel apenas consulta. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ShiftForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShiftsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PatrolsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShifts::route('/'),
            'edit' => EditShift::route('/{record}/edit'),
        ];
    }
}
