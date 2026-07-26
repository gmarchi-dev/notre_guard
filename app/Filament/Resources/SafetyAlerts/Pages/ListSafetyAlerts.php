<?php

namespace App\Filament\Resources\SafetyAlerts\Pages;

use App\Filament\Resources\SafetyAlerts\SafetyAlertResource;
use Filament\Resources\Pages\ListRecords;

class ListSafetyAlerts extends ListRecords
{
    protected static string $resource = SafetyAlertResource::class;

    protected static ?string $title = 'Alertas de segurança';

    // Alertas nascem em campo ou no agendador; ninguém cria alerta pelo painel.
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getSubheading(): ?string
    {
        return 'Reconhecer é dizer "estou vendo". Quem confirma se o vigilante está bem é o rádio, não o sistema.';
    }
}
