<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case CreditCard = 'credit_card';
    case DebitCard = 'debit_card';
    case Pix = 'pix';
    case Boleto = 'boleto';
    case BankDebit = 'bank_debit';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function requiresStoredMethod(): bool
    {
        return in_array($this, [self::CreditCard, self::DebitCard], true);
    }
}
