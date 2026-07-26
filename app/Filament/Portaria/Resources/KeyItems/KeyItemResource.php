<?php

namespace App\Filament\Portaria\Resources\KeyItems;

use App\Filament\Portaria\Resources\KeyItems\Pages\ListKeyItems;
use App\Filament\Portaria\Resources\KeyItems\Tables\KeyItemsTable;
use App\Models\KeyItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * O quadro de chaves. Tela principal da portaria: mostra o que está pendurado
 * e o que está na mão de alguém.
 */
class KeyItemResource extends Resource
{
    protected static ?string $model = KeyItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $modelLabel = 'chave';

    protected static ?string $pluralModelLabel = 'quadro de chaves';

    protected static ?int $navigationSort = 1;

    /** O cadastro de chaves é da supervisão; a portaria opera o quadro. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return KeyItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKeyItems::route('/'),
        ];
    }
}
