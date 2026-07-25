<?php

namespace App\Filament\Resources\PatrolRoutes\Pages;

use App\Filament\Resources\PatrolRoutes\PatrolRouteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPatrolRoute extends EditRecord
{
    protected static string $resource = PatrolRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
