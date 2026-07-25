<?php

namespace App\Filament\Resources\SecurityGuards\Pages;

use App\Filament\Resources\SecurityGuards\SecurityGuardResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSecurityGuard extends CreateRecord
{
    protected static string $resource = SecurityGuardResource::class;
}
