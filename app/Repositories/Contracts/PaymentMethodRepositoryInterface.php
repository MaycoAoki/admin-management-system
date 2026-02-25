<?php

namespace App\Repositories\Contracts;

use App\Models\PaymentMethod;
use Illuminate\Support\Collection;

interface PaymentMethodRepositoryInterface
{
    /** @return Collection<int, PaymentMethod> */
    public function forUser(int $userId): Collection;

    public function findById(int $id): ?PaymentMethod;

    public function findDefaultForUser(int $userId): ?PaymentMethod;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): PaymentMethod;

    /** @param array<string, mixed> $attributes */
    public function update(PaymentMethod $paymentMethod, array $attributes): PaymentMethod;

    public function delete(PaymentMethod $paymentMethod): void;

    public function setDefault(PaymentMethod $paymentMethod): void;
}
