<?php

namespace App\Notifications;

use App\Filament\Resources\SafetyAlerts\SafetyAlertResource;
use App\Models\SafetyAlert;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de pânico ou inatividade.
 *
 * **Não implementa ShouldQueue de propósito.** Um botão de pânico que espera o
 * worker da fila subir é um botão quebrado. O envio acontece na própria
 * requisição: pânico é raro, e aqui a certeza vale mais que a latência.
 */
class SafetyAlertRaised extends Notification
{
    public function __construct(public readonly SafetyAlert $alert) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $alert = $this->alert->loadMissing(['unit', 'securityGuard.user', 'patrol.patrolRoute']);
        $urgent = $alert->isPanic();

        $mail = (new MailMessage)
            ->subject(sprintf(
                '%s[%s] %s - %s',
                $urgent ? 'URGENTE: ' : '',
                $alert->unit->code,
                $alert->kindLabel(),
                $alert->securityGuard?->user?->name ?? 'vigilante',
            ))
            ->greeting($urgent ? 'Acionamento de emergência' : 'Vigilante sem registro em ronda')
            ->line('**Unidade:** '.$alert->unit->name)
            ->line('**Vigilante:** '.($alert->securityGuard?->user?->name ?? '—')
                .' ('.($alert->securityGuard?->registration ?? '—').')')
            ->line('**Hora:** '.$alert->occurred_at->format('d/m/Y H:i:s'));

        if ($alert->kind === SafetyAlert::KIND_INACTIVITY) {
            $mail->line('**Silêncio:** '.$alert->silence_minutes.' minutos sem nenhum registro')
                ->line('**Ronda:** '.($alert->patrol?->patrolRoute?->name ?? '—'));
        }

        $mail->line($this->locationLine($alert));

        if ($urgent) {
            $mail->line('---')
                ->line('**Confirme o atendimento por rádio ou telefone antes de considerar resolvido.**');
        }

        return $mail
            ->action('Abrir alerta', SafetyAlertResource::getUrl('index'))
            ->salutation('Notre Guard');
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $alert = $this->alert->loadMissing(['unit', 'securityGuard.user']);

        return FilamentNotification::make()
            ->title(($alert->isPanic() ? '🚨 ' : '').$alert->kindLabel())
            ->body(sprintf(
                '%s · %s · %s',
                $alert->securityGuard?->user?->name ?? 'vigilante',
                $alert->unit->name,
                $alert->occurred_at->format('d/m H:i'),
            ))
            ->icon($alert->isPanic() ? 'heroicon-o-bell-alert' : 'heroicon-o-clock')
            ->color($alert->isPanic() ? 'danger' : 'warning')
            ->actions([
                Action::make('open')
                    ->label('Atender')
                    ->url(SafetyAlertResource::getUrl('index'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    private function locationLine(SafetyAlert $alert): string
    {
        if ($alert->latitude === null || $alert->longitude === null) {
            return '**Localização:** não disponível (aparelho sem GPS no momento do acionamento)';
        }

        return sprintf(
            '**Localização:** %s, %s - https://maps.google.com/?q=%s,%s',
            $alert->latitude,
            $alert->longitude,
            $alert->latitude,
            $alert->longitude,
        );
    }
}
