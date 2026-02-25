<?php

namespace App\Repositories;

use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Support\Collection;

class PlanRepository implements PlanRepositoryInterface
{
    /** @return Collection<int, Plan> */
    public function allActive(): Collection
    {
        return Plan::query()->where('is_active', true)->orderBy('price_in_cents')->get();
    }

    public function findById(int $id): ?Plan
    {
        return Plan::query()->find($id);
    }

    public function findBySlug(string $slug): ?Plan
    {
        return Plan::query()->where('slug', $slug)->first();
    }
}
