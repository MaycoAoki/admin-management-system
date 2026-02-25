<?php

namespace App\Models;

use App\Enums\BillingCycle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_in_cents',
        'currency',
        'billing_cycle',
        'trial_days',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'price_in_cents' => 'integer',
            'billing_cycle' => BillingCycle::class,
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
