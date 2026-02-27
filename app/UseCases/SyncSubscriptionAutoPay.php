<?php

namespace App\UseCases;

use App\Notifications\AutoPayDisabledNotification;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;

final class SyncSubscriptionAutoPay
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
    ) {}

    public function execute(int $userId): void
    {
        $subscription = $this->subscriptionRepository->findActiveForUser($userId);

        if (! $subscription || ! $subscription->auto_pay) {
            return;
        }

        $defaultPaymentMethod = $this->paymentMethodRepository->findDefaultForUser($userId);

        if ($defaultPaymentMethod && $defaultPaymentMethod->type->supportsAutomaticCharge()) {
            return;
        }

        $subscription = $this->subscriptionRepository->update($subscription, [
            'auto_pay' => false,
        ]);

        $subscription->user->notify(new AutoPayDisabledNotification($subscription));
    }
}
