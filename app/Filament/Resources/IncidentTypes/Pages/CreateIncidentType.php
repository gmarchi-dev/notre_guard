<?php

namespace App\Filament\Resources\IncidentTypes\Pages;

use App\Filament\Resources\IncidentTypes\IncidentTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIncidentType extends CreateRecord
{
    protected static string $resource = IncidentTypeResource::class;
}
