<?php

namespace App\Filament\Resources\SecurityGuards;

use App\Filament\Resources\SecurityGuards\Pages\CreateSecurityGuard;
use App\Filament\Resources\SecurityGuards\Pages\EditSecurityGuard;
use App\Filament\Resources\SecurityGuards\Pages\ListSecurityGuards;
use App\Filament\Resources\SecurityGuards\Schemas\SecurityGuardForm;
use App\Filament\Resources\SecurityGuards\Tables\SecurityGuardsTable;
use App\Models\SecurityGuard;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SecurityGuardResource extends Resource
{
    protected static ?string $model = SecurityGuard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?string $modelLabel = 'vigilante';

    protected static ?string $pluralModelLabel = 'vigilantes';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return SecurityGuardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SecurityGuardsTable::configure($table);
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
            'index' => ListSecurityGuards::route('/'),
            'create' => CreateSecurityGuard::route('/create'),
            'edit' => EditSecurityGuard::route('/{record}/edit'),
        ];
    }
}
