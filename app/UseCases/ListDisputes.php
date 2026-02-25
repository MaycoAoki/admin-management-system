<?php

namespace App\UseCases;

use App\Enums\DisputeStatus;
use App\Repositories\Contracts\DisputeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListDisputes
{
    public function __construct(
        private readonly DisputeRepositoryInterface $disputeRepository,
    ) {}

    public function execute(int $userId, int $perPage = 15, ?DisputeStatus $status = null): LengthAwarePaginator
    {
        return $this->disputeRepository->paginateForUser(
            userId: $userId,
            perPage: min($perPage, 50),
            status: $status,
        );
    }
}
