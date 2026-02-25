<?php

namespace App\Repositories\Contracts;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PaymentRepositoryInterface
{
    /** @return LengthAwarePaginator<Payment> */
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /** @return Collection<int, Payment> */
    public function forInvoice(int $invoiceId): Collection;

    public function findById(int $id): ?Payment;

    public function findByIdWithRelations(int $id): ?Payment;

    public function findByGatewayPaymentId(string $gatewayPaymentId): ?Payment;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Payment;

    /** @param array<string, mixed> $attributes */
    public function update(Payment $payment, array $attributes): Payment;

    public function hasPendingForPaymentMethod(int $paymentMethodId): bool;
}
