<?php

namespace App\Console\Commands;

use App\Notifications\InvoiceDueSoonNotification;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Console\Command;

class SendDueSoonReminders extends Command
{
    protected $signature = 'billing:send-due-soon-reminders
                            {--days=3 : Number of days ahead to check for due invoices}';

    protected $description = 'Send email reminders for invoices due soon';

    public function __construct(private readonly InvoiceRepositoryInterface $invoiceRepository)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $daysAhead = (int) $this->option('days');
        $invoices = $this->invoiceRepository->dueSoon($daysAhead);

        $this->info("Found {$invoices->count()} invoice(s) due in {$daysAhead} day(s).");

        foreach ($invoices as $invoice) {
            $invoice->user->notify(new InvoiceDueSoonNotification($invoice));
            $this->line("  → Notified user #{$invoice->user_id} for invoice #{$invoice->invoice_number}");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
