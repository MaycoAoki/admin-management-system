<?php

namespace App\DTOs;

use App\Models\Invoice;
use App\Models\Subscription;

final class DashboardData
{
    public function __construct(
        public readonly int $outstandingInCents,
        public readonly int $openInvoicesCount,
        public readonly int $overdueInvoicesCount,
        public readonly ?Invoice $nextDue,
        public readonly ?Subscription $subscription,
    ) {}
}
