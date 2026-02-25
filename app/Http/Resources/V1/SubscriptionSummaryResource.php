<?php

namespace App\Http\Resources\V1;

use App\Enums\SubscriptionStatus;
use App\Traits\FormatsMonetary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionSummaryResource extends JsonResource
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
            'plan_name' => $this->plan->name,
            'plan_slug' => $this->plan->slug,
            'billing_cycle' => $this->plan->billing_cycle->value,
            'price_in_cents' => $this->plan->price_in_cents,
            'price_formatted' => $this->formatCurrency($this->plan->price_in_cents, $this->plan->currency),
            'current_period_end' => $this->current_period_end,
            'auto_renew' => $this->auto_renew,
            'is_trial' => $this->status === SubscriptionStatus::Trialing,
            'trial_ends_at' => $this->trial_ends_at,
        ];
    }
}
