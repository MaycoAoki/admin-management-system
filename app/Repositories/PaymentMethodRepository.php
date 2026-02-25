<?php

namespace App\Repositories;

use App\Models\PaymentMethod;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentMethodRepository implements PaymentMethodRepositoryInterface
{
    /** @return Collection<int, PaymentMethod> */
    public function forUser(int $userId): Collection
    {
        return PaymentMethod::query()
            ->where('user_id', $userId)
            ->orderByDesc('is_default')
            ->get();
    }

    public function findById(int $id): ?PaymentMethod
    {
        return PaymentMethod::query()->find($id);
    }

    public function findDefaultForUser(int $userId): ?PaymentMethod
    {
        return PaymentMethod::query()
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->first();
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): PaymentMethod
    {
        return PaymentMethod::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(PaymentMethod $paymentMethod, array $attributes): PaymentMethod
    {
        $paymentMethod->update($attributes);

        return $paymentMethod->fresh();
    }

    public function delete(PaymentMethod $paymentMethod): void
    {
        $paymentMethod->delete();
    }

    public function setDefault(PaymentMethod $paymentMethod): void
    {
        DB::transaction(function () use ($paymentMethod) {
            PaymentMethod::query()
                ->where('user_id', $paymentMethod->user_id)
                ->where('id', '!=', $paymentMethod->id)
                ->update(['is_default' => false]);

            $paymentMethod->update(['is_default' => true]);
        });
    }
}
