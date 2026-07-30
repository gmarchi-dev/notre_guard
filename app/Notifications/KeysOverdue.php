<?php

namespace App\Notifications;

use App\Filament\Resources\KeyLoans\KeyLoanResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Resumo das chaves fora do prazo.
 *
 * Um aviso com a lista, e não um por chave: cinco e-mails separados às 19h
 * viram cinco e-mails ignorados.
 */
class KeysOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param Collection<int, \App\Models\KeyLoan> $loans */
    public function __construct(public readonly Collection $loans) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(sprintf('%d chave(s) não devolvida(s)', $this->loans->count()))
            ->greeting('Chaves fora do prazo')
            ->line('As chaves abaixo passaram do horário de devolução informado na retirada.');

        foreach ($this->loans as $loan) {
            $mail->line(sprintf(
                '- **%s** (%s) - com %s desde %s, prazo %s',
                $loan->keyItem?->code,
                $loan->keyItem?->name,
                $loan->holder?->name,
                $loan->released_at->format('d/m H:i'),
                $loan->due_at->format('d/m H:i'),
            ));
        }

        return $mail
            ->action('Abrir o livro de retiradas', KeyLoanResource::getUrl('index'))
            ->salutation('Notre Guard');
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->loans->count().' chave(s) não devolvida(s)')
            ->body($this->loans->take(3)->map(fn ($l) => $l->keyItem?->code.' com '.$l->holder?->name)->join(' · '))
            ->icon('heroicon-o-key')
            ->color('warning')
            ->actions([
                Action::make('open')
                    ->label('Ver')
                    ->url(KeyLoanResource::getUrl('index'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
