<?php

namespace App\Filament\Resources\PatrolRoutes\Pages;

use App\Filament\Resources\PatrolRoutes\PatrolRouteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPatrolRoutes extends ListRecords
{
    protected static string $resource = PatrolRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
