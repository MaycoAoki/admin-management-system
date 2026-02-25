<?php

namespace App\UseCases;

use App\Models\Plan;
use App\Models\Subscription;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Validation\ValidationException;

final class ChangePlan
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
        $subscription = $this->subscriptionRepository->findActiveForUser($userId);

        if (! $subscription) {
            throw ValidationException::withMessages([
                'subscription' => ['No active subscription found.'],
            ]);
        }

        if ($subscription->plan_id === $planId) {
            throw ValidationException::withMessages([
                'plan_id' => ['Already subscribed to this plan.'],
            ]);
        }

        /** @var Plan $plan */
        $plan = $this->planRepository->findById($planId);

        $subscription = $this->subscriptionRepository->update($subscription, [
            'plan_id' => $plan->id,
        ]);

        $subscription->load('plan');

        return $subscription;
    }
}
