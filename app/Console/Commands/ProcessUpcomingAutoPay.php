<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\UseCases\ProcessInvoiceAutoPay;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class ProcessUpcomingAutoPay extends Command
{
    protected $signature = 'billing:process-upcoming-auto-pay
                            {--days= : Number of days ahead eligible for automatic charge}';

    protected $description = 'Attempt automatic charges for invoices that are due soon';

    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly ProcessInvoiceAutoPay $processInvoiceAutoPay,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $daysOption = $this->option('days');
        $daysAhead = $daysOption !== null
            ? (int) $daysOption
            : (int) config('billing.auto_pay.advance_days', 1);
        $invoices = $this->invoiceRepository->upcomingForAutoPay($daysAhead);

        $this->info("Found {$invoices->count()} invoice(s) eligible in the next {$daysAhead} day(s).");

        foreach ($invoices as $invoice) {
            try {
                if ($this->processInvoiceAutoPay->execute($invoice)) {
                    $this->line("  -> Auto-paid invoice #{$invoice->invoice_number} for user #{$invoice->user_id}");
                }
            } catch (AuthorizationException|ModelNotFoundException|ValidationException) {
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
