<?php

namespace App\UseCases;

use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetCurrentSubscription
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
    ) {}

    /**
     * @throws ModelNotFoundException
     */
    public function execute(int $userId): Subscription
    {
        $subscription = $this->subscriptionRepository->findActiveForUser($userId);

        if (! $subscription) {
            throw new ModelNotFoundException;
        }

        return $subscription;
    }
}
