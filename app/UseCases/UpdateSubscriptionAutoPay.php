<?php

namespace App\UseCases;

use App\Models\Subscription;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Validation\ValidationException;

final class UpdateSubscriptionAutoPay
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(int $userId, bool $autoPay): Subscription
    {
        $subscription = $this->subscriptionRepository->findActiveForUser($userId);

        if (! $subscription) {
            throw ValidationException::withMessages([
                'subscription' => ['No active subscription found.'],
            ]);
        }

        if ($autoPay) {
            $defaultPaymentMethod = $this->paymentMethodRepository->findDefaultForUser($userId);

            if (! $defaultPaymentMethod || ! $defaultPaymentMethod->type->supportsAutomaticCharge()) {
                throw ValidationException::withMessages([
                    'auto_pay' => ['Auto-pay requires an eligible default payment method.'],
                ]);
            }
        }

        $subscription = $this->subscriptionRepository->update($subscription, [
            'auto_pay' => $autoPay,
        ]);

        $subscription->load('plan');

        return $subscription;
    }
}
