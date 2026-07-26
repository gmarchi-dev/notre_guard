<?php

use Illuminate\Support\Facades\Schedule;

// Expurgo diário conforme config/retention.php. De madrugada porque apagar
// arquivo de evidência é I/O e não deve competir com a ronda noturna.
//
// Exige o agendador do Laravel ativo (cron chamando `schedule:run`).
Schedule::command('notre-guard:purge-data')
    ->dailyAt('03:30')
    ->onOneServer()
    ->withoutOverlapping();

// Vigilância de inatividade em ronda. A cada 5 minutos: o intervalo define a
// pior latência entre o vigilante parar de registrar e a supervisão saber.
Schedule::command('notre-guard:watch-inactivity')
    ->everyFiveMinutes()
    ->onOneServer()
    ->withoutOverlapping();

// Chaves não devolvidas: uma vez no fim do expediente. Avisar de hora em hora
// transformaria a pendência em ruído, e a portaria já vê o atraso no quadro.
Schedule::command('notre-guard:overdue-keys')
    ->dailyAt('19:00')
    ->weekdays()
    ->onOneServer();
