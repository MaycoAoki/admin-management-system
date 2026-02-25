<?php

namespace App\UseCases;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Validation\ValidationException;

final class CancelSubscription
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(int $userId): Subscription
    {
        $subscription = $this->subscriptionRepository->findActiveForUser($userId);

        if (! $subscription) {
            throw ValidationException::withMessages([
                'subscription' => ['No active subscription found.'],
            ]);
        }

        $subscription = $this->subscriptionRepository->update($subscription, [
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => now(),
            'cancel_at' => $subscription->current_period_end,
            'auto_renew' => false,
        ]);

        $subscription->load('plan');

        return $subscription;
    }
}
