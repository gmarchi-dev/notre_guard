<?php

namespace App\Filament\Resources\KeyItems\Pages;

use App\Filament\Resources\KeyItems\KeyItemResource;
use Filament\Resources\Pages\ListRecords;

class ListKeyItems extends ListRecords
{
    protected static string $resource = KeyItemResource::class;

    protected static ?string $title = 'Chaves';

    public function getSubheading(): ?string
    {
        return 'A entrega e a devolução são feitas no painel da portaria.';
    }
}
