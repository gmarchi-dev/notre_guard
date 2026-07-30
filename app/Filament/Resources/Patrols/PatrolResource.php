<?php

namespace App\Filament\Resources\Patrols;

use App\Filament\Concerns\ScopedToUnit;
use App\Filament\Resources\Patrols\Pages\EditPatrol;
use App\Filament\Resources\Patrols\Pages\ListPatrols;
use App\Filament\Resources\Patrols\Schemas\PatrolForm;
use App\Filament\Resources\Patrols\Tables\PatrolsTable;
use App\Models\Patrol;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PatrolResource extends Resource
{
    use ScopedToUnit;

    protected static ?string $model = Patrol::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'Operação';

    protected static ?string $modelLabel = 'ronda';

    protected static ?string $pluralModelLabel = 'rondas';

    protected static ?int $navigationSort = 3;

    /** Rondas nascem em campo - o painel apenas consulta. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return PatrolForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatrolsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ScansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        // Sem página de criação: rondas nascem no aparelho do vigilante.
        return [
            'index' => ListPatrols::route('/'),
            'edit' => EditPatrol::route('/{record}/edit'),
        ];
    }
}
