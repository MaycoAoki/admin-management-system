<?php

namespace App\UseCases;

use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class RemovePaymentMethod
{
    public function __construct(
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {}

    /**
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(int $paymentMethodId, int $userId): void
    {
        $paymentMethod = $this->paymentMethodRepository->findById($paymentMethodId);

        if (! $paymentMethod) {
            throw new ModelNotFoundException;
        }

        if ($paymentMethod->user_id !== $userId) {
            throw new AuthorizationException;
        }

        if ($this->paymentRepository->hasPendingForPaymentMethod($paymentMethodId)) {
            throw ValidationException::withMessages([
                'payment_method' => ['Payment method has pending payments.'],
            ]);
        }

        $this->paymentMethodRepository->delete($paymentMethod);
    }
}
