<?php

namespace App\UseCases;

use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Validation\ValidationException;

final class UpdateSubscriptionAutoRenew
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(int $userId, bool $autoRenew): Subscription
    {
        $subscription = $this->subscriptionRepository->findActiveForUser($userId);

        if (! $subscription) {
            throw ValidationException::withMessages([
                'subscription' => ['No active subscription found.'],
            ]);
        }

        $subscription = $this->subscriptionRepository->update($subscription, [
            'auto_renew' => $autoRenew,
        ]);

        $subscription->load('plan');

        return $subscription;
    }
}
