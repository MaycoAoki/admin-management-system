<?php

namespace App\Repositories;

use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Support\Collection;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function findActiveForUser(int $userId): ?Subscription
    {
        return Subscription::query()
            ->with('plan')
            ->forUser($userId)
            ->active()
            ->latest()
            ->first();
    }

    public function findById(int $id): ?Subscription
    {
        return Subscription::query()->with('plan')->find($id);
    }

    /** @return Collection<int, Subscription> */
    public function historyForUser(int $userId): Collection
    {
        return Subscription::query()
            ->with('plan')
            ->forUser($userId)
            ->withTrashed()
            ->latest()
            ->get();
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Subscription
    {
        return Subscription::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Subscription $subscription, array $attributes): Subscription
    {
        $subscription->update($attributes);

        return $subscription->fresh();
    }
}
