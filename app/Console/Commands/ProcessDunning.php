<?php

namespace App\Console\Commands;

use App\Notifications\InvoiceOverdueNotification;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Console\Command;

class ProcessDunning extends Command
{
    protected $signature = 'billing:process-dunning';

    protected $description = 'Send dunning notifications for all overdue invoices';

    public function __construct(private readonly InvoiceRepositoryInterface $invoiceRepository)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $invoices = $this->invoiceRepository->overdue();

        $this->info("Found {$invoices->count()} overdue invoice(s).");

        foreach ($invoices as $invoice) {
            $invoice->user->notify(new InvoiceOverdueNotification($invoice));
            $this->line("  → Notified user #{$invoice->user_id} for invoice #{$invoice->invoice_number}");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
