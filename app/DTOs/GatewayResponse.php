<?php

namespace App\DTOs;

use App\Enums\PaymentStatus;
use Illuminate\Support\Carbon;

final class GatewayResponse
{
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly string $gatewayPaymentId,
        public readonly ?string $pixQrCode = null,
        public readonly ?Carbon $pixExpiresAt = null,
        public readonly ?string $boletoUrl = null,
        public readonly ?string $boletoBarcode = null,
        public readonly ?Carbon $boletoExpiresAt = null,
        public readonly ?string $failureReason = null,
        public readonly ?Carbon $paidAt = null,
        public readonly ?Carbon $failedAt = null,
    ) {}
}
