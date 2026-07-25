<?php

namespace App\Filament\Resources\Patrols\Pages;

use App\Filament\Resources\Patrols\PatrolResource;
use Filament\Resources\Pages\ListRecords;

class ListPatrols extends ListRecords
{
    protected static string $resource = PatrolResource::class;

    // Sem ação de criar: rondas nascem no aparelho do vigilante.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
