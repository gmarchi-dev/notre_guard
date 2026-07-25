<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\User;
use App\Notifications\IncidentReported;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Decide quem é avisado de uma ocorrência, e quando.
 */
class IncidentNotifier
{
    /** Gravidades que sempre disparam aviso. */
    private const URGENT_SEVERITIES = ['high', 'critical'];

    public function notify(Incident $incident): void
    {
        if (! $this->deservesNotification($incident)) {
            return;
        }

        $recipients = $this->recipientsFor($incident);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new IncidentReported($incident));
    }

    /**
     * Gravidade alta ou crítica, ou tipo marcado como "notificar supervisão" —
     * que é como a gestão sinaliza um assunto sensível independente da
     * gravidade que o vigilante escolheu em campo.
     */
    public function deservesNotification(Incident $incident): bool
    {
        if (in_array($incident->severity, self::URGENT_SEVERITIES, true)) {
            return true;
        }

        return (bool) $incident->loadMissing('type')->type?->notify_supervision;
    }

    /**
     * Administração e supervisão recebem tudo; gestor de unidade recebe o que
     * é da unidade dele. Vigilante nunca recebe: ele está em campo, não em
     * posição de tratar.
     *
     * @return Collection<int, User>
     */
    public function recipientsFor(Incident $incident): Collection
    {
        return User::query()
            ->where('active', true)
            ->where(function ($query) use ($incident) {
                $query
                    ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR])
                    ->orWhere(fn ($q) => $q
                        ->where('role', User::ROLE_UNIT_MANAGER)
                        ->where('unit_id', $incident->unit_id));
            })
            ->get();
    }
}
