<?php

namespace App\Filament\Resources\Shifts\Pages;

use App\Filament\Resources\Shifts\ShiftResource;
use Filament\Resources\Pages\ListRecords;

class ListShifts extends ListRecords
{
    protected static string $resource = ShiftResource::class;

    // Sem ação de criar: turnos nascem na assunção de posto, em campo.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
