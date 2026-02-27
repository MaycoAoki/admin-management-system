<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification implements ShouldQueue
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
            ->subject('Falha no pagamento')
            ->greeting("Olá, {$notifiable->name}!")
            ->line("Não foi possível processar seu pagamento de R$ {$amount}.")
            ->when(
                $this->payment->failure_reason,
                fn (MailMessage $mail) => $mail->line("Motivo: {$this->payment->failure_reason}")
            )
            ->line('Por favor, verifique seu método de pagamento e tente novamente.')
            ->line('Se precisar de ajuda, entre em contato com o suporte.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'amount_in_cents' => $this->payment->amount_in_cents,
            'invoice_id' => $this->payment->invoice_id,
            'failure_reason' => $this->payment->failure_reason,
        ];
    }
}
