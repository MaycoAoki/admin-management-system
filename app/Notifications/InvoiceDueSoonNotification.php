<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceDueSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Invoice $invoice) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->invoice->amount_due_in_cents / 100, 2, ',', '.');
        $dueDate = $this->invoice->due_date->format('d/m/Y');

        return (new MailMessage)
            ->subject('Fatura vencendo em breve')
            ->greeting("Olá, {$notifiable->name}!")
            ->line("Sua fatura #{$this->invoice->invoice_number} no valor de R$ {$amount} vence em {$dueDate}.")
            ->line('Evite juros e multas realizando o pagamento antes do vencimento.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount_due_in_cents' => $this->invoice->amount_due_in_cents,
            'due_date' => $this->invoice->due_date->toDateString(),
        ];
    }
}
