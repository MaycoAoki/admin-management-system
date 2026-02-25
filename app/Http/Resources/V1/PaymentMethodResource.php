<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'is_default' => $this->is_default,
            'brand' => $this->brand,
            'last_four' => $this->last_four,
            'expiry_month' => $this->expiry_month,
            'expiry_year' => $this->expiry_year,
            'holder_name' => $this->holder_name,
            'pix_key' => $this->pix_key,
            'bank_name' => $this->bank_name,
            'created_at' => $this->created_at,
        ];
    }
}
