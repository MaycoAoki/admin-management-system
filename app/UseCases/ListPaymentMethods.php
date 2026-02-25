<?php

namespace App\UseCases;

use App\Models\PaymentMethod;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use Illuminate\Support\Collection;

final class ListPaymentMethods
{
    public function __construct(
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
    ) {}

    /** @return Collection<int, PaymentMethod> */
    public function execute(int $userId): Collection
    {
        return $this->paymentMethodRepository->forUser($userId);
    }
}
