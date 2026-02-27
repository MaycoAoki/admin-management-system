<?php

namespace App\UseCases;

use App\DTOs\InitiatePaymentData;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class ProcessInvoiceAutoPay
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly InitiatePayment $initiatePayment,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function execute(Invoice $invoice): bool
    {
        $subscription = $this->subscriptionRepository->findActiveForUser($invoice->user_id);

        if (! $subscription || ! $subscription->auto_pay) {
            return false;
        }

        $defaultPaymentMethod = $this->paymentMethodRepository->findDefaultForUser($invoice->user_id);

        if (! $defaultPaymentMethod || ! $defaultPaymentMethod->type->supportsAutomaticCharge()) {
            return false;
        }

        $hasPendingPayment = $this->paymentRepository
            ->forInvoice($invoice->id)
            ->contains(fn ($payment) => $payment->status === PaymentStatus::Pending);

        if ($hasPendingPayment) {
            return false;
        }

        $payment = $this->initiatePayment->execute(
            invoiceId: $invoice->id,
            userId: $invoice->user_id,
            data: new InitiatePaymentData(
                methodType: $defaultPaymentMethod->type,
                paymentMethodId: $defaultPaymentMethod->id,
            ),
        );

        return $payment->status === PaymentStatus::Succeeded;
    }
}
