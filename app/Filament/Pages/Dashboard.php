<?php

namespace App\Filament\Pages;

use App\Models\Unit;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public const PERIODS = [
        '7' => 'Últimos 7 dias',
        '30' => 'Últimos 30 dias',
        '90' => 'Últimos 90 dias',
    ];

    public function getTitle(): string
    {
        return 'Painel operacional';
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('period')
                ->label('Período')
                ->options(self::PERIODS)
                ->default('30')
                ->selectablePlaceholder(false),

            Select::make('unit_id')
                ->label('Unidade')
                ->options(fn () => Unit::query()->orderBy('name')->pluck('name', 'id'))
                // Gestor de unidade não escolhe: o filtro fica travado na dele.
                ->default(fn () => auth()->user()->unit_id)
                ->disabled(fn () => auth()->user()->isScopedToUnit())
                ->placeholder('Todas as unidades'),
        ]);
    }
}
