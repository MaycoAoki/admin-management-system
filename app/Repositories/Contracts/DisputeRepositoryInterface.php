<?php

namespace App\Repositories\Contracts;

use App\Enums\DisputeStatus;
use App\Models\Dispute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DisputeRepositoryInterface
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Dispute;

    public function findById(int $id): ?Dispute;

    public function findByIdWithPayment(int $id): ?Dispute;

    /** @return LengthAwarePaginator<Dispute> */
    public function paginateForUser(int $userId, int $perPage = 15, ?DisputeStatus $status = null): LengthAwarePaginator;

    public function hasActiveForPayment(int $paymentId): bool;

    /** @param array<string, mixed> $attributes */
    public function update(Dispute $dispute, array $attributes): Dispute;
}
