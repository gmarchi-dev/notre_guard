<?php

namespace App\Filament\Portaria\Widgets;

use App\Models\KeyItem;
use App\Models\KeyLoan;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Estado do quadro, em três números.
 *
 * A pergunta da portaria na troca de turno é sempre a mesma: o que está fora e
 * o que está atrasado. Até aqui a resposta exigia ler a tabela linha a linha,
 * ou reparar num badge vermelho no menu lateral - o que só funciona para quem
 * já sabe que ele existe.
 */
class KeyBoardOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can(User::PERMISSION_KEYS) ?? false;
    }

    protected function getStats(): array
    {
        $total = KeyItem::query()->where('active', true)->count();
        $out = KeyItem::query()->where('active', true)->out()->count();
        $overdue = KeyLoan::query()->overdue()->count();

        return [
            Stat::make('No quadro', (string) max($total - $out, 0))
                ->description($total.' chave(s) cadastrada(s)')
                ->descriptionIcon('heroicon-m-key')
                ->color('success'),

            Stat::make('Fora do quadro', (string) $out)
                ->description($out === 0 ? 'nenhuma retirada em aberto' : 'retiradas em aberto')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color($out > 0 ? 'warning' : 'gray'),

            // Fica cinza quando é zero de propósito: vermelho permanente vira
            // paisagem, e aí deixa de ser aviso no dia em que houver atraso.
            Stat::make('Atrasadas', (string) $overdue)
                ->description($overdue === 0 ? 'nada vencido' : 'passaram do prazo combinado')
                ->descriptionIcon($overdue > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($overdue > 0 ? 'danger' : 'gray'),
        ];
    }
}
