<?php

namespace App\Repositories\Contracts;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InvoiceRepositoryInterface
{
    /** @return LengthAwarePaginator<Invoice> */
    public function paginateForUser(int $userId, int $perPage = 15, ?InvoiceStatus $status = null): LengthAwarePaginator;

    public function findByIdWithRelations(int $id): ?Invoice;

    public function findById(int $id): ?Invoice;

    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice;

    /** @return Collection<int, Invoice> */
    public function openForUser(int $userId): Collection;

    public function nextDueForUser(int $userId): ?Invoice;

    public function outstandingBalanceForUser(int $userId): int;

    /** @return Collection<int, Invoice> */
    public function dueSoon(int $daysAhead = 3): Collection;

    /** @return Collection<int, Invoice> */
    public function overdue(): Collection;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Invoice;

    /** @param array<string, mixed> $attributes */
    public function update(Invoice $invoice, array $attributes): Invoice;
}
