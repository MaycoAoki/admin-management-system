<?php

namespace App\Repositories;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    /** @return LengthAwarePaginator<Invoice> */
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Invoice::query()
            ->forUser($userId)
            ->latest('due_date')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Invoice
    {
        return Invoice::query()->find($id);
    }

    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice
    {
        return Invoice::query()->where('invoice_number', $invoiceNumber)->first();
    }

    /** @return Collection<int, Invoice> */
    public function openForUser(int $userId): Collection
    {
        return Invoice::query()
            ->forUser($userId)
            ->open()
            ->orderBy('due_date')
            ->get();
    }

    public function nextDueForUser(int $userId): ?Invoice
    {
        return Invoice::query()
            ->forUser($userId)
            ->open()
            ->where('due_date', '>=', now()->toDateString())
            ->orderBy('due_date')
            ->first();
    }

    public function outstandingBalanceForUser(int $userId): int
    {
        return (int) Invoice::query()
            ->forUser($userId)
            ->open()
            ->sum(DB::raw('amount_in_cents - amount_paid_in_cents'));
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Invoice
    {
        return Invoice::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Invoice $invoice, array $attributes): Invoice
    {
        $invoice->update($attributes);

        return $invoice->fresh();
    }
}
