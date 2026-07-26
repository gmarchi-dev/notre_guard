<?php

namespace App\Filament\Resources\SafetyAlerts;

use App\Filament\Concerns\ScopedToUnit;
use App\Filament\Resources\SafetyAlerts\Pages\ListSafetyAlerts;
use App\Filament\Resources\SafetyAlerts\Tables\SafetyAlertsTable;
use App\Models\SafetyAlert;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SafetyAlertResource extends Resource
{
    use ScopedToUnit;

    protected static ?string $model = SafetyAlert::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'Operação';

    protected static ?string $modelLabel = 'alerta';

    protected static ?string $pluralModelLabel = 'alertas de segurança';

    /** Primeiro item do grupo: é o que precisa ser visto antes de tudo. */
    protected static ?int $navigationSort = -1;

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Contador na navegação com os alertas em aberto — o número que a supervisão
     * precisa ver sem abrir a tela.
     */
    public static function getNavigationBadge(): ?string
    {
        $open = static::getEloquentQuery()->where('status', 'open')->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $hasPanic = static::getEloquentQuery()
            ->where('status', 'open')
            ->where('kind', SafetyAlert::KIND_PANIC)
            ->exists();

        return $hasPanic ? 'danger' : 'warning';
    }

    public static function table(Table $table): Table
    {
        return SafetyAlertsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSafetyAlerts::route('/'),
        ];
    }
}
