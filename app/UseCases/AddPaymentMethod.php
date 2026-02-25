<?php

namespace App\UseCases;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentMethod;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;

final class AddPaymentMethod
{
    public function __construct(
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
        private readonly PaymentGatewayInterface $gateway,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(int $userId, array $attributes): PaymentMethod
    {
        $hasExisting = $this->paymentMethodRepository->forUser($userId)->isNotEmpty();

        $gatewayToken = $this->gateway->tokenize($attributes);

        return $this->paymentMethodRepository->create([
            'user_id' => $userId,
            'type' => $attributes['type'],
            'gateway' => 'stub',
            'gateway_token' => $gatewayToken,
            'is_default' => ! $hasExisting,
            'last_four' => $attributes['last_four'] ?? null,
            'brand' => $attributes['brand'] ?? null,
            'expiry_month' => $attributes['expiry_month'] ?? null,
            'expiry_year' => $attributes['expiry_year'] ?? null,
            'holder_name' => $attributes['holder_name'] ?? null,
            'pix_key' => $attributes['pix_key'] ?? null,
            'bank_name' => $attributes['bank_name'] ?? null,
        ]);
    }
}
