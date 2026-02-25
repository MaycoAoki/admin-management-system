<?php

namespace App\DTOs;

use App\Enums\PaymentMethodType;

final class InitiatePaymentData
{
    public function __construct(
        public readonly PaymentMethodType $methodType,
        public readonly ?int $amountInCents = null,
        public readonly ?int $paymentMethodId = null,
    ) {}
}
