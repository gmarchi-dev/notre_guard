<?php

namespace App\Filament\Portaria\Resources\KeyLoans;

use App\Filament\Portaria\Resources\KeyLoans\Pages\ListKeyLoans;
use App\Filament\Portaria\Resources\KeyLoans\Tables\KeyLoansTable;
use App\Models\KeyLoan;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KeyLoanResource extends Resource
{
    protected static ?string $model = KeyLoan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'retirada';

    protected static ?string $pluralModelLabel = 'livro de retiradas';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->can(User::PERMISSION_KEYS) ?? false;
    }

    public static function canCreate(): bool
    {
        // Retirada nasce no quadro de chaves, com a chave em mãos.
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $overdue = KeyLoan::query()->overdue()->count();

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
