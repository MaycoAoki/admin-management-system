<?php

namespace App\Http\Resources\V1;

use App\Traits\FormatsMonetary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    use FormatsMonetary;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'billing_cycle' => $this->billing_cycle->value,
            'price_in_cents' => $this->price_in_cents,
            'price_formatted' => $this->formatCurrency($this->price_in_cents, $this->currency),
            'currency' => $this->currency,
            'trial_days' => $this->trial_days,
        ];
    }
}
