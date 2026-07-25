<?php

namespace App\Filament\Resources\Shifts\Pages;

use App\Filament\Resources\Shifts\ShiftResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Ficha de consulta do turno. Registro de campo não se edita pelo painel.
 */
class EditShift extends EditRecord
{
    protected static string $resource = ShiftResource::class;

    protected static ?string $title = 'Turno';

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
