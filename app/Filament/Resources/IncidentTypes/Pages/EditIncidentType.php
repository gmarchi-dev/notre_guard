<?php

namespace App\Filament\Resources\IncidentTypes\Pages;

use App\Filament\Resources\IncidentTypes\IncidentTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIncidentType extends EditRecord
{
    protected static string $resource = IncidentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
