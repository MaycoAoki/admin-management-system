<?php

namespace App\Repositories;

use App\Enums\DisputeStatus;
use App\Models\Dispute;
use App\Repositories\Contracts\DisputeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DisputeRepository implements DisputeRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Dispute
    {
        return Dispute::query()->create($attributes);
    }

    public function findById(int $id): ?Dispute
    {
        return Dispute::query()->find($id);
    }

    public function findByIdWithPayment(int $id): ?Dispute
    {
        return Dispute::query()->with('payment')->find($id);
    }

    /** @return LengthAwarePaginator<Dispute> */
    public function paginateForUser(int $userId, int $perPage = 15, ?DisputeStatus $status = null): LengthAwarePaginator
    {
        return Dispute::query()
            ->where('user_id', $userId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }

    public function hasActiveForPayment(int $paymentId): bool
    {
        return Dispute::query()
            ->where('payment_id', $paymentId)
            ->whereIn('status', [DisputeStatus::Open, DisputeStatus::UnderReview])
            ->exists();
    }

    /** @param array<string, mixed> $attributes */
    public function update(Dispute $dispute, array $attributes): Dispute
    {
        $dispute->update($attributes);

        return $dispute->fresh();
    }
}
