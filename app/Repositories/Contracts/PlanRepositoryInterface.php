<?php

namespace App\Repositories\Contracts;

use App\Models\Plan;
use Illuminate\Support\Collection;

interface PlanRepositoryInterface
{
    /** @return Collection<int, Plan> */
    public function allActive(): Collection;

    public function findById(int $id): ?Plan;

    public function findBySlug(string $slug): ?Plan;
}
