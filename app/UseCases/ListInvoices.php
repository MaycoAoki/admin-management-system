<?php

namespace App\UseCases;

use App\Enums\InvoiceStatus;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListInvoices
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function execute(int $userId, int $perPage = 15, ?InvoiceStatus $status = null): LengthAwarePaginator
    {
        return $this->invoiceRepository->paginateForUser(
            userId: $userId,
            perPage: min($perPage, 50),
            status: $status,
        );
    }
}
