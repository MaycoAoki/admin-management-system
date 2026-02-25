<?php

namespace App\UseCases;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\DisputeStatus;
use App\Enums\PaymentStatus;
use App\Models\Dispute;
use App\Repositories\Contracts\DisputeRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class OpenDispute
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly DisputeRepositoryInterface $disputeRepository,
        private readonly PaymentGatewayInterface $gateway,
    ) {}

    /**
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(int $paymentId, int $userId, string $reason, ?string $description): Dispute
    {
        $payment = $this->paymentRepository->findById($paymentId);

        if (! $payment) {
            throw new ModelNotFoundException;
        }

        if ($payment->user_id !== $userId) {
            throw new AuthorizationException;
        }

        if ($payment->status !== PaymentStatus::Succeeded) {
            throw ValidationException::withMessages([
                'payment' => ['Only succeeded payments can be disputed.'],
            ]);
        }

        if ($this->disputeRepository->hasActiveForPayment($paymentId)) {
            throw ValidationException::withMessages([
                'payment' => ['Payment already has an active dispute.'],
            ]);
        }

        $gatewayDisputeId = $this->gateway->openDispute($payment, $reason);

        $dispute = $this->disputeRepository->create([
            'user_id' => $userId,
            'payment_id' => $paymentId,
            'status' => DisputeStatus::Open,
            'reason' => $reason,
            'description' => $description,
            'gateway_dispute_id' => $gatewayDisputeId,
        ]);

        $dispute->load('payment');

        return $dispute;
    }
}
