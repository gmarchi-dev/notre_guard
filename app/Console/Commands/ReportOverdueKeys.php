<?php

namespace App\Console\Commands;

use App\Models\Unit;
use App\Notifications\KeysOverdue;
use App\Services\KeyCustody;
use App\Services\SupervisionAudience;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class ReportOverdueKeys extends Command
{
    protected $signature = 'notre-guard:overdue-keys';

    protected $description = 'Avisa a supervisão sobre chaves não devolvidas no prazo';

    public function handle(KeyCustody $custody, SupervisionAudience $audience): int
    {
        $total = 0;

        // Um aviso por unidade: quem recebe precisa saber de qual portaria se
        // trata, e o gestor de unidade não deve ver a lista da outra.
        foreach (Unit::query()->where('active', true)->get() as $unit) {
            $overdue = $custody->overdue($unit->id);

            if ($overdue->isEmpty()) {
                continue;
            }

            $recipients = $audience->for($unit->id);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new KeysOverdue($overdue));
            }

            $this->components->warn("{$unit->code}: {$overdue->count()} chave(s) em atraso.");
            $total += $overdue->count();
        }

        if ($total === 0) {
            $this->components->info('Nenhuma chave em atraso.');
        }

        return self::SUCCESS;
    }
}
