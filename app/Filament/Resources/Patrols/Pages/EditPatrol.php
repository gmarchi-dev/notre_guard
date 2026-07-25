<?php

namespace App\Filament\Resources\Patrols\Pages;

use App\Filament\Resources\Patrols\PatrolResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Ficha de consulta da ronda. Sem salvar e sem excluir: registro de campo não
 * se edita nem se apaga pelo painel.
 */
class EditPatrol extends EditRecord
{
    protected static string $resource = PatrolResource::class;

    protected static ?string $title = 'Ronda';

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
