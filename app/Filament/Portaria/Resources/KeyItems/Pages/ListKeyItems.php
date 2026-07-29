<?php

namespace App\Filament\Portaria\Resources\KeyItems\Pages;

use App\Filament\Portaria\Resources\KeyItems\KeyItemResource;
use App\Filament\Portaria\Widgets\KeyBoardOverview;
use Filament\Resources\Pages\ListRecords;

class ListKeyItems extends ListRecords
{
    protected static string $resource = KeyItemResource::class;

    protected static ?string $title = 'Quadro de chaves';

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            KeyBoardOverview::class,
        ];
    }
}
