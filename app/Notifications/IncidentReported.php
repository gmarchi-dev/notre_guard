<?php

namespace App\Notifications;

use App\Filament\Resources\Incidents\IncidentResource;
use App\Models\Incident;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Avisa a supervisão de uma ocorrência que não pode esperar o RDO do dia
 * seguinte.
 */
class IncidentReported extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Incident $incident)
    {
        // A ocorrência é criada dentro da transação do evento de sincronização.
        // Sem isto, a fila poderia processar a notificação antes do commit e
        // não encontrar o registro. (A propriedade vem do trait Queueable, por
        // isso é atribuída aqui em vez de redeclarada.)
        $this->afterCommit = true;
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $incident = $this->incident->loadMissing(['unit', 'type.parent', 'reportedBy.user']);

        return (new MailMessage)
            ->subject("[{$incident->unit->code}] {$this->severityLabel()} — {$incident->number}")
            ->greeting("Ocorrência {$incident->number}")
            ->line("**Unidade:** {$incident->unit->name}")
            ->line('**Tipo:** '.$incident->type->fullName())
            ->line('**Gravidade:** '.$this->severityLabel())
            ->line('**Hora do fato:** '.$incident->occurred_at->format('d/m/Y H:i'))
            ->line('**Local:** '.($incident->location ?: 'não informado'))
            ->line('**Registrada por:** '.($incident->reportedBy?->user?->name ?? '—'))
            ->line('---')
            ->line($incident->description)
            ->when(filled($incident->actions_taken), fn (MailMessage $mail) => $mail
                ->line('**Providências tomadas:** '.$incident->actions_taken))
            ->action('Abrir no Notre Guard', IncidentResource::getUrl('edit', ['record' => $incident]))
            ->salutation('Notre Guard');
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $incident = $this->incident->loadMissing(['unit', 'type']);

        return FilamentNotification::make()
            ->title("{$incident->number} — ".$incident->type->fullName())
            ->body("{$incident->unit->name} · ".$incident->occurred_at->format('d/m H:i'))
            ->icon('heroicon-o-exclamation-triangle')
            ->color($incident->severity === 'critical' ? 'danger' : 'warning')
            ->actions([
                Action::make('open')
                    ->label('Abrir')
                    ->url(IncidentResource::getUrl('edit', ['record' => $incident]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    private function severityLabel(): string
    {
        return Incident::SEVERITIES[$this->incident->severity] ?? $this->incident->severity;
    }
}
