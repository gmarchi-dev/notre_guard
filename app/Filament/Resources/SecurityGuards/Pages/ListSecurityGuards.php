<?php

namespace App\Filament\Resources\SecurityGuards\Pages;

use App\Filament\Resources\SecurityGuards\SecurityGuardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSecurityGuards extends ListRecords
{
    protected static string $resource = SecurityGuardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
