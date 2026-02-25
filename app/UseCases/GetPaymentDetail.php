<?php

namespace App\UseCases;

use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetPaymentDetail
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {}

    /**
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function execute(int $paymentId, int $userId): Payment
    {
        $payment = $this->paymentRepository->findByIdWithRelations($paymentId);

        if (! $payment) {
            throw new ModelNotFoundException;
        }

        if ($payment->user_id !== $userId) {
            throw new AuthorizationException;
        }

        return $payment;
    }
}
