<?php

namespace App\Enums;

enum DisputeStatus: string
{
    case Open = 'open';
    case UnderReview = 'under_review';
    case Won = 'won';
    case Lost = 'lost';
    case Withdrawn = 'withdrawn';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isWithdrawable(): bool
    {
        return $this === self::Open;
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::UnderReview], true);
    }
}
