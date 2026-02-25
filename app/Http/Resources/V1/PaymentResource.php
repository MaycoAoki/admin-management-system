<?php

namespace App\Http\Resources\V1;

use App\Traits\FormatsMonetary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    use FormatsMonetary;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount_in_cents' => $this->amount_in_cents,
            'amount_formatted' => $this->formatCurrency($this->amount_in_cents, $this->currency),
            'status' => $this->status->value,
            'payment_method_type' => $this->payment_method_type->value,
            'gateway' => $this->gateway,
            'failure_reason' => $this->failure_reason,
            'paid_at' => $this->paid_at,
            'failed_at' => $this->failed_at,
            'created_at' => $this->created_at,
        ];
    }
}
