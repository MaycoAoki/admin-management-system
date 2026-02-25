<?php

namespace App\Http\Resources\V1;

use App\DTOs\DashboardData;
use App\Traits\FormatsMonetary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    use FormatsMonetary;

    public function __construct(public readonly DashboardData $data) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'balance' => [
                'outstanding_in_cents' => $this->data->outstandingInCents,
                'outstanding_formatted' => $this->formatCurrency($this->data->outstandingInCents),
                'open_invoices_count' => $this->data->openInvoicesCount,
                'overdue_invoices_count' => $this->data->overdueInvoicesCount,
            ],
            'next_due' => $this->resolveNextDue(),
            'subscription' => $this->data->subscription
                ? new SubscriptionSummaryResource($this->data->subscription)
                : null,
        ];
    }

    /** @return array<string, mixed>|null */
    private function resolveNextDue(): ?array
    {
        $invoice = $this->data->nextDue;

        if (! $invoice) {
            return null;
        }

        $daysUntilDue = (int) now()->startOfDay()->diffInDays($invoice->due_date->startOfDay(), false);

        return [
            'invoice_number' => $invoice->invoice_number,
            'amount_due_in_cents' => $invoice->amount_due_in_cents,
            'amount_due_formatted' => $this->formatCurrency($invoice->amount_due_in_cents, $invoice->currency),
            'due_date' => $invoice->due_date->toDateString(),
            'is_overdue' => $invoice->due_date->isPast(),
            'days_until_due' => $daysUntilDue,
        ];
    }
}
