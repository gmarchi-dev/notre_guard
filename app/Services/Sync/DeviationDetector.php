<?php

namespace App\Services\Sync;

use App\Models\Checkpoint;
use App\Models\Patrol;
use App\Models\PatrolScan;
use App\Support\Geo;
use Illuminate\Support\Carbon;

/**
 * Calcula desvios de uma leitura de ponto.
 *
 * Nada aqui recusa um registro. Desvio é informação para o supervisor — app que
 * recusa registro produz vigilante que anota em papel.
 */
class DeviationDetector
{
    /**
     * @return array{0: list<string>, 1: int|null} desvios e distância em metros
     */
    public function forScan(
        Checkpoint $checkpoint,
        Patrol $patrol,
        Carbon $occurredAt,
        ?float $latitude,
        ?float $longitude,
        bool $clockUntrustworthy,
    ): array {
        $deviations = [];
        $distance = null;

        if ($latitude === null || $longitude === null) {
            $deviations[] = PatrolScan::DEVIATION_NO_GPS;
        } elseif ($checkpoint->latitude !== null && $checkpoint->longitude !== null) {
            $distance = (int) round(Geo::distanceMeters(
                (float) $checkpoint->latitude,
                (float) $checkpoint->longitude,
                $latitude,
                $longitude,
            ));

            if ($distance > $checkpoint->radius_m) {
                $deviations[] = PatrolScan::DEVIATION_OUT_OF_RADIUS;
            }
        }

        if ($clockUntrustworthy) {
            $deviations[] = PatrolScan::DEVIATION_CLOCK_SKEW;
        }

        if ($this->outsideSchedule($patrol, $occurredAt)) {
            $deviations[] = PatrolScan::DEVIATION_OUT_OF_WINDOW;
        }

        if ($this->outOfOrder($patrol, $checkpoint)) {
            $deviations[] = PatrolScan::DEVIATION_OUT_OF_ORDER;
        }

        return [$deviations, $distance];
    }

    /**
     * Roteiro sem janela cadastrada pode ser executado a qualquer hora.
     */
    private function outsideSchedule(Patrol $patrol, Carbon $occurredAt): bool
    {
        $schedules = $patrol->patrolRoute->schedules()->where('active', true)->get();

        if ($schedules->isEmpty()) {
            return false;
        }

        $weekday = (int) $occurredAt->dayOfWeek;
        $time = $occurredAt->format('H:i:s');
        $tolerance = $patrol->patrolRoute->tolerance_min;

        foreach ($schedules as $schedule) {
            if (! in_array($weekday, (array) $schedule->weekdays, true)) {
                continue;
            }

            $start = Carbon::parse($schedule->window_start)->subMinutes($tolerance)->format('H:i:s');
            $end = Carbon::parse($schedule->window_end)->addMinutes($tolerance)->format('H:i:s');

            // Janela que atravessa a meia-noite (ex.: 23:00 → 01:00).
            $matches = $start <= $end
                ? ($time >= $start && $time <= $end)
                : ($time >= $start || $time <= $end);

            if ($matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Só faz sentido em roteiro ordenado: ler um ponto cuja posição é anterior
     * à maior já lida significa que o vigilante voltou ou pulou trecho.
     */
    private function outOfOrder(Patrol $patrol, Checkpoint $checkpoint): bool
    {
        if (! $patrol->patrolRoute->ordered) {
            return false;
        }

        $position = $patrol->patrolRoute->checkpoints()
            ->where('checkpoints.id', $checkpoint->id)
            ->value('patrol_route_checkpoints.position');

        if ($position === null) {
            return false;
        }

        $highestScanned = $patrol->scans()
            ->join('patrol_route_checkpoints as prc', function ($join) use ($patrol) {
                $join->on('prc.checkpoint_id', '=', 'patrol_scans.checkpoint_id')
                    ->where('prc.patrol_route_id', '=', $patrol->patrol_route_id);
            })
            ->max('prc.position');

        return $highestScanned !== null && $position < (int) $highestScanned;
    }
}
