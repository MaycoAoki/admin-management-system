<?php

namespace App\Enums;

enum DisputeReason: string
{
    case Fraudulent = 'fraudulent';
    case Duplicate = 'duplicate';
    case ProductNotReceived = 'product_not_received';
    case ProductNotAsDescribed = 'product_not_as_described';
    case Unrecognized = 'unrecognized';
    case Other = 'other';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
