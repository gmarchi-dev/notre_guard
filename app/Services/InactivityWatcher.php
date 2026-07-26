<?php

namespace App\Services;

use App\Models\Patrol;
use App\Models\PatrolScan;
use App\Models\SafetyAlert;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Detecta vigilante que iniciou uma ronda e parou de registrar.
 *
 * Vigia **rondas em andamento**, não turnos. Um vigilante em portaria pode
 * passar horas sem ler um ponto, e isso é normal — alertar nesse caso geraria
 * ruído constante. Ronda iniciada e silenciosa, não: ou aconteceu algo com ele,
 * ou o aparelho ficou sem bateria, e as duas coisas a supervisão precisa saber.
 *
 * O alerta não substitui procedimento: quem confirma se o vigilante está bem é
 * o rádio, não o sistema.
 */
class InactivityWatcher
{
    public function thresholdMinutes(): int
    {
        return (int) config('safety.inactivity_minutes');
    }

    /**
     * @return int quantidade de alertas criados nesta passagem
     */
    public function sweep(?Carbon $now = null): int
    {
        $now = $now ?? now();
        $threshold = $this->thresholdMinutes();
        $created = 0;

        Patrol::query()
            ->where('status', 'in_progress')
            // Um alerta por ronda: enquanto o silêncio durar, não repetir.
            ->whereDoesntHave('safetyAlerts', fn ($q) => $q->where('kind', SafetyAlert::KIND_INACTIVITY))
            ->with('shift.securityGuard')
            ->chunkById(100, function ($patrols) use ($now, $threshold, &$created) {
                foreach ($patrols as $patrol) {
                    $silence = $this->silenceMinutes($patrol, $now);

                    if ($silence < $threshold) {
                        continue;
                    }

                    $guard = $patrol->shift?->securityGuard;

                    if (! $guard) {
                        continue;
                    }

                    $last = $this->lastKnownPosition($patrol);

                    SafetyAlert::create([
                        'uuid' => (string) Str::uuid7(),
                        'kind' => SafetyAlert::KIND_INACTIVITY,
                        'security_guard_id' => $guard->id,
                        'unit_id' => $patrol->unit_id,
                        'shift_id' => $patrol->shift_id,
                        'patrol_id' => $patrol->id,
                        'occurred_at' => $now,
                        'received_at' => $now,
                        // A última posição conhecida é o que orienta a busca —
                        // não é onde ele está, é onde foi visto por último.
                        'latitude' => $last?->latitude,
                        'longitude' => $last?->longitude,
                        'silence_minutes' => $silence,
                    ]);

                    $created++;
                }
            });

        return $created;
    }

    /**
     * Silêncio medido pela última leitura; se não houve nenhuma, pelo início da
     * ronda. Usa occurred_at (hora do aparelho) porque é quando o vigilante
     * agiu de fato — received_at pode estar atrasado por falta de rede, e isso
     * não é inatividade.
     */
    public function silenceMinutes(Patrol $patrol, ?Carbon $now = null): int
    {
        $now = $now ?? now();
        $lastActivity = $patrol->scans()->max('occurred_at');

        $reference = $lastActivity
            ? Carbon::parse($lastActivity)
            : $patrol->started_at;

        return (int) floor($reference->diffInMinutes($now, absolute: true));
    }

    private function lastKnownPosition(Patrol $patrol): ?PatrolScan
    {
        return $patrol->scans()
            ->whereNotNull('latitude')
            ->latest('occurred_at')
            ->first();
    }
}
