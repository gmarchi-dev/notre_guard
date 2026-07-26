<?php

namespace App\Observers;

use App\Models\SafetyAlert;
use App\Notifications\SafetyAlertRaised;
use App\Services\SupervisionAudience;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SafetyAlertObserver
{
    public function __construct(private readonly SupervisionAudience $audience) {}

    public function created(SafetyAlert $alert): void
    {
        $recipients = $this->audience->for($alert->unit_id);

        if ($recipients->isEmpty()) {
            Log::error('Alerta de segurança sem ninguém para avisar', [
                'alert' => $alert->id,
                'kind' => $alert->kind,
            ]);

            return;
        }

        try {
            Notification::send($recipients, new SafetyAlertRaised($alert));
        } catch (Throwable $e) {
            // Falha no aviso não pode desfazer o registro do alerta: o
            // acionamento do vigilante fica gravado de qualquer forma, e a
            // supervisão ainda o vê na tela de alertas.
            Log::error('Falha ao notificar alerta de segurança', [
                'alert' => $alert->id,
                'exception' => $e,
            ]);
        }
    }
}
