<?php

namespace App\Contracts;

use App\DTOs\GatewayResponse;
use App\Models\Payment;
use App\Models\PaymentMethod;

interface PaymentGatewayInterface
{
    public function charge(Payment $payment, ?PaymentMethod $paymentMethod = null): GatewayResponse;
}
