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
     * Gravidade alta ou crítica, ou tipo marcado como "notificar supervisão" -
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

    /** @return Collection<int, User> */
    public function recipientsFor(Incident $incident): Collection
    {
        return app(SupervisionAudience::class)->for($incident->unit_id);
    }
}
