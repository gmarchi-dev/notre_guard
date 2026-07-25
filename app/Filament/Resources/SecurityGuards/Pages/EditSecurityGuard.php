<?php

namespace App\Filament\Resources\SecurityGuards\Pages;

use App\Filament\Resources\SecurityGuards\SecurityGuardResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSecurityGuard extends EditRecord
{
    protected static string $resource = SecurityGuardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
