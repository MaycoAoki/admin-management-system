<?php

namespace App\Repositories;

use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PaymentRepository implements PaymentRepositoryInterface
{
    /** @return LengthAwarePaginator<Payment> */
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::query()
            ->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    /** @return Collection<int, Payment> */
    public function forInvoice(int $invoiceId): Collection
    {
        return Payment::query()
            ->where('invoice_id', $invoiceId)
            ->latest()
            ->get();
    }

    public function findById(int $id): ?Payment
    {
        return Payment::query()->find($id);
    }

    public function findByGatewayPaymentId(string $gatewayPaymentId): ?Payment
    {
        return Payment::query()->where('gateway_payment_id', $gatewayPaymentId)->first();
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Payment
    {
        return Payment::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Payment $payment, array $attributes): Payment
    {
        $payment->update($attributes);

        return $payment->fresh();
    }
}
