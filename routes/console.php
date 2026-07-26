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
