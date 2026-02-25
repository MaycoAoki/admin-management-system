<?php

namespace App\Repositories\Contracts;

use App\Models\Subscription;
use Illuminate\Support\Collection;

interface SubscriptionRepositoryInterface
{
    public function findActiveForUser(int $userId): ?Subscription;

    public function findById(int $id): ?Subscription;

    /** @return Collection<int, Subscription> */
    public function historyForUser(int $userId): Collection;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Subscription;

    /** @param array<string, mixed> $attributes */
    public function update(Subscription $subscription, array $attributes): Subscription;
}
