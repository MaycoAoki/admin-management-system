<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AutoPayDisabledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Subscription $subscription)
    {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Auto-pay desativado')
            ->greeting("Olá, {$notifiable->name}!")
            ->line('O auto-pay da sua assinatura foi desativado automaticamente.')
            ->line('Isso aconteceu porque o método de pagamento padrão atual não é elegível para cobrança automática.')
            ->line('Defina um cartão ou débito em conta como padrão e reative o auto-pay se quiser continuar com a cobrança automática.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'auto_pay' => false,
        ];
    }
}
