<?php

namespace App\UseCases;

use App\DTOs\DashboardData;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;

final class GetDashboardSummary
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
    ) {}

    public function execute(int $userId): DashboardData
    {
        $openInvoices = $this->invoiceRepository->openForUser($userId);
        $nextDue = $this->invoiceRepository->nextDueForUser($userId);
        $outstanding = $this->invoiceRepository->outstandingBalanceForUser($userId);
        $subscription = $this->subscriptionRepository->findActiveForUser($userId);

        return new DashboardData(
            outstandingInCents: $outstanding,
            openInvoicesCount: $openInvoices->count(),
            overdueInvoicesCount: $openInvoices->filter(fn ($invoice) => $invoice->due_date->isPast())->count(),
            nextDue: $nextDue,
            subscription: $subscription,
        );
    }
}
