<?php

namespace App\UseCases;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Validation\ValidationException;

final class SubscribeToPlan
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PlanRepositoryInterface $planRepository,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(int $userId, int $planId): Subscription
    {
        $existing = $this->subscriptionRepository->findActiveForUser($userId);

        if ($existing) {
            throw ValidationException::withMessages([
                'subscription' => ['User already has an active subscription.'],
            ]);
        }

        /** @var Plan $plan */
        $plan = $this->planRepository->findById($planId);

        $start = now();
        $end = match ($plan->billing_cycle) {
            BillingCycle::Monthly => $start->copy()->addMonth(),
            BillingCycle::Quarterly => $start->copy()->addMonths(3),
            BillingCycle::Semiannual => $start->copy()->addMonths(6),
            BillingCycle::Annual => $start->copy()->addYear(),
        };

        $isTrial = $plan->trial_days > 0;

        $subscription = $this->subscriptionRepository->create([
            'user_id' => $userId,
            'plan_id' => $plan->id,
            'status' => $isTrial ? SubscriptionStatus::Trialing : SubscriptionStatus::Active,
            'current_period_start' => $start,
            'current_period_end' => $end,
            'trial_ends_at' => $isTrial ? $start->copy()->addDays($plan->trial_days) : null,
            'auto_renew' => true,
        ]);

        $subscription->load('plan');

        return $subscription;
    }
}
