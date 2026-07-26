<?php

namespace App\Filament\Resources\Incidents\Pages;

use App\Filament\Resources\Incidents\IncidentResource;
use Filament\Resources\Pages\ListRecords;

class ListIncidents extends ListRecords
{
    protected static string $resource = IncidentResource::class;

    // Sem ação de criar: ocorrência nasce no aplicativo do vigilante.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
