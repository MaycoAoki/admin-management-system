<?php

namespace App\Http\Resources\V1;

use App\Enums\InvoiceStatus;
use App\Traits\FormatsMonetary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    use FormatsMonetary;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status->value,
            'amount_in_cents' => $this->amount_in_cents,
            'amount_paid_in_cents' => $this->amount_paid_in_cents,
            'amount_due_in_cents' => $this->amount_due_in_cents,
            'amount_formatted' => $this->formatCurrency($this->amount_in_cents, $this->currency),
            'currency' => $this->currency,
            'description' => $this->description,
            'due_date' => $this->due_date?->toDateString(),
            'paid_at' => $this->paid_at,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'is_overdue' => $this->status === InvoiceStatus::Open && $this->due_date?->isPast(),
            'created_at' => $this->created_at,
            // Campos exclusivos do detalhe
            'pdf_url' => $this->when($this->relationLoaded('subscription'), fn () => null),
            'subscription' => $this->whenLoaded('subscription', fn () => $this->subscription
                ? new SubscriptionSummaryResource($this->subscription)
                : null
            ),
            'payments' => $this->whenLoaded('payments', fn () => PaymentResource::collection($this->payments)),
        ];
    }
}
