<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case CreditCard = 'credit_card';
    case DebitCard = 'debit_card';
    case Pix = 'pix';
    case Boleto = 'boleto';
    case BankDebit = 'bank_debit';
}
