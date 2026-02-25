<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\GatewayResponse;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Support\Str;

class StubGateway implements PaymentGatewayInterface
{
    /** @param array<string, mixed> $attributes */
    public function tokenize(array $attributes): string
    {
        return 'stub_token_'.Str::random(20);
    }

    public function openDispute(Payment $payment, string $reason): string
    {
        return 'stub_dispute_'.Str::random(16);
    }

    public function charge(Payment $payment, ?PaymentMethod $paymentMethod = null): GatewayResponse
    {
        $gatewayId = 'stub_'.Str::uuid();

        return match ($payment->payment_method_type) {
            PaymentMethodType::Pix => new GatewayResponse(
                status: PaymentStatus::Pending,
                gatewayPaymentId: $gatewayId,
                pixQrCode: '00020126'.Str::random(40),
                pixExpiresAt: now()->addHour(),
            ),
            PaymentMethodType::Boleto => new GatewayResponse(
                status: PaymentStatus::Pending,
                gatewayPaymentId: $gatewayId,
                boletoUrl: 'https://boleto.stub/'.$gatewayId,
                boletoBarcode: fake()->numerify('####.##### #####.###### #####.###### # ##############'),
                boletoExpiresAt: now()->addDays(3),
            ),
            default => new GatewayResponse(
                status: PaymentStatus::Succeeded,
                gatewayPaymentId: $gatewayId,
                paidAt: now(),
            ),
        };
    }
}
