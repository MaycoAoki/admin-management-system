<?php

namespace App\Traits;

trait FormatsMonetary
{
    protected function formatCurrency(int $amountInCents, string $currency = 'BRL'): string
    {
        return match ($currency) {
            'BRL' => 'R$ '.number_format($amountInCents / 100, 2, ',', '.'),
            default => number_format($amountInCents / 100, 2).' '.$currency,
        };
    }
}
