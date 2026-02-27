<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdueNotification extends Notification implements ShouldQueue
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
            ->subject('Fatura em atraso')
            ->greeting("Olá, {$notifiable->name}!")
            ->line("Sua fatura #{$this->invoice->invoice_number} no valor de R$ {$amount} venceu em {$dueDate} e ainda não foi paga.")
            ->line('Regularize sua situação o quanto antes para evitar a suspensão do seu serviço.')
            ->line('Se já realizou o pagamento, por favor desconsidere este aviso.');
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
