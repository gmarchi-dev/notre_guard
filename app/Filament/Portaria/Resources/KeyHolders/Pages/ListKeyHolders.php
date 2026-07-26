<?php

namespace App\Filament\Portaria\Resources\KeyHolders\Pages;

use App\Filament\Portaria\Resources\KeyHolders\KeyHolderResource;
use Filament\Resources\Pages\ListRecords;

class ListKeyHolders extends ListRecords
{
    protected static string $resource = KeyHolderResource::class;

    protected static ?string $title = 'Solicitantes';
}
