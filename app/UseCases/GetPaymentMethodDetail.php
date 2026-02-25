<?php

namespace App\UseCases;

use App\Models\PaymentMethod;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetPaymentMethodDetail
{
    public function __construct(
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
    ) {}

    /**
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function execute(int $paymentMethodId, int $userId): PaymentMethod
    {
        $paymentMethod = $this->paymentMethodRepository->findById($paymentMethodId);

        if (! $paymentMethod) {
            throw new ModelNotFoundException;
        }

        if ($paymentMethod->user_id !== $userId) {
            throw new AuthorizationException;
        }

        return $paymentMethod;
    }
}
