<?php

namespace App\Filament\Resources\RetentionRuns;

use App\Filament\Resources\RetentionRuns\Pages\ListRetentionRuns;
use App\Filament\Resources\RetentionRuns\Tables\RetentionRunsTable;
use App\Models\RetentionRun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Histórico de expurgos. Existe para demonstrar conformidade: a LGPD exige
 * poder comprovar a eliminação, não apenas executá-la.
 */
class RetentionRunResource extends Resource
{
    protected static ?string $model = RetentionRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrash;

    protected static string|UnitEnum|null $navigationGroup = 'Configuração';

    protected static ?string $modelLabel = 'expurgo';

    protected static ?string $pluralModelLabel = 'retenção de dados';

    protected static ?int $navigationSort = 4;

    /** Registro de conformidade: consulta apenas, e só para administrador. */
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return RetentionRunsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRetentionRuns::route('/'),
        ];
    }
}
