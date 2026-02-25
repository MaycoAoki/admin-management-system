<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'auto_renew' => $this->auto_renew,
            'current_period_start' => $this->current_period_start,
            'current_period_end' => $this->current_period_end,
            'trial_ends_at' => $this->trial_ends_at,
            'canceled_at' => $this->canceled_at,
            'cancel_at' => $this->cancel_at,
            'plan' => new PlanResource($this->whenLoaded('plan', fn () => $this->plan)),
        ];
    }
}
