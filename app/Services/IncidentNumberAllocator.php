<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

/**
 * Numeração RO NNN/AAAA, sequencial por unidade e ano.
 *
 * A sequência é alocada no servidor, nunca no dispositivo: dois aparelhos
 * offline na mesma unidade chegariam ao mesmo número.
 */
class IncidentNumberAllocator
{
    /**
     * @return array{sequence: int, year: int, number: string}
     */
    public function allocate(Unit $unit, int $year): array
    {
        return DB::transaction(function () use ($unit, $year) {
            $last = Incident::query()
                ->where('unit_id', $unit->id)
                ->where('year', $year)
                ->lockForUpdate()
                ->max('sequence');

            $sequence = ((int) $last) + 1;

            return [
                'sequence' => $sequence,
                'year' => $year,
                'number' => sprintf('RO %03d/%d', $sequence, $year),
            ];
        });
    }
}
