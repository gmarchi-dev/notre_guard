<?php

namespace App\Filament\Resources\IncidentTypes\Pages;

use App\Filament\Resources\IncidentTypes\IncidentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIncidentTypes extends ListRecords
{
    protected static string $resource = IncidentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
