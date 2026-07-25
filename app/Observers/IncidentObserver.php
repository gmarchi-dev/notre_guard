<?php

namespace App\Observers;

use App\Models\Incident;
use App\Services\IncidentNotifier;

class IncidentObserver
{
    public function __construct(private readonly IncidentNotifier $notifier) {}

    /**
     * O aviso sai na criação, não na análise: uma ocorrência grave que espera
     * alguém abrir o painel já perdeu o propósito.
     *
     * A notificação é enfileirada com afterCommit, então ela só é despachada
     * depois que a transação do evento de sincronização fecha.
     */
    public function created(Incident $incident): void
    {
        $this->notifier->notify($incident);
    }
}
