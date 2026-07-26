<?php

namespace App\Filament\Resources\KeyLoans;

use App\Filament\Concerns\ScopedToUnit;
use App\Filament\Portaria\Resources\KeyLoans\Tables\KeyLoansTable;
use App\Filament\Resources\KeyLoans\Pages\ListKeyLoans;
use App\Models\KeyLoan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Livro de retiradas para a supervisão. Reaproveita a mesma tabela da portaria:
 * é a mesma informação, e duplicar a definição faria as duas telas divergirem
 * na primeira alteração.
 */
class KeyLoanResource extends Resource
{
    use ScopedToUnit;

    protected static ?string $model = KeyLoan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Operação';

    protected static ?string $modelLabel = 'retirada de chave';

    protected static ?string $pluralModelLabel = 'retiradas de chave';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $overdue = static::getEloquentQuery()->overdue()->count();

        return $overdue > 0 ? (string) $overdue : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return KeyLoansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKeyLoans::route('/'),
        ];
    }
}
