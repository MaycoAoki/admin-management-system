<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSucceededNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Payment $payment)
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
        $amount = number_format($this->payment->amount_in_cents / 100, 2, ',', '.');

        return (new MailMessage)
            ->subject('Pagamento confirmado')
            ->greeting("Olá, {$notifiable->name}!")
            ->line("Seu pagamento de R$ {$amount} foi processado com sucesso.")
            ->line("Fatura: #{$this->payment->invoice_id}")
            ->line("Método: {$this->payment->payment_method_type->value}")
            ->line('Obrigado por manter sua assinatura em dia!');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'amount_in_cents' => $this->payment->amount_in_cents,
            'invoice_id' => $this->payment->invoice_id,
        ];
    }
}
