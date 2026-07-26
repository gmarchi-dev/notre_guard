<?php

namespace App\Console\Commands;

use App\Services\InactivityWatcher;
use Illuminate\Console\Command;

class WatchInactivity extends Command
{
    protected $signature = 'notre-guard:watch-inactivity';

    protected $description = 'Alerta a supervisão sobre rondas em andamento sem registro';

    public function handle(InactivityWatcher $watcher): int
    {
        $created = $watcher->sweep();

        $this->components->info(sprintf(
            'Limite de %d min · %d alerta(s) de inatividade criado(s).',
            $watcher->thresholdMinutes(),
            $created,
        ));

        return self::SUCCESS;
    }
}
