<?php

namespace App\Http\Resources\V1;

use App\Traits\FormatsMonetary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisputeResource extends JsonResource
{
    use FormatsMonetary;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'reason' => $this->reason->value,
            'description' => $this->description,
            'gateway_dispute_id' => $this->gateway_dispute_id,
            'resolved_at' => $this->resolved_at,
            'withdrawn_at' => $this->withdrawn_at,
            'payment' => $this->whenLoaded('payment', fn () => [
                'id' => $this->payment->id,
                'status' => $this->payment->status->value,
                'amount_in_cents' => $this->payment->amount_in_cents,
                'amount_formatted' => $this->formatCurrency($this->payment->amount_in_cents, $this->payment->currency),
                'payment_method_type' => $this->payment->payment_method_type->value,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
