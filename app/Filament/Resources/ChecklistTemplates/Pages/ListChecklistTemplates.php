<?php

namespace App\Filament\Resources\ChecklistTemplates\Pages;

use App\Filament\Resources\ChecklistTemplates\ChecklistTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChecklistTemplates extends ListRecords
{
    protected static string $resource = ChecklistTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
