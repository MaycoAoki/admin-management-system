<?php

namespace App\UseCases;

use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListPayments
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {}

    public function execute(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paymentRepository->paginateForUser(
            userId: $userId,
            perPage: min($perPage, 50),
        );
    }
}
