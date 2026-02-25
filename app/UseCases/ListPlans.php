<?php

namespace App\UseCases;

use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Support\Collection;

final class ListPlans
{
    public function __construct(
        private readonly PlanRepositoryInterface $planRepository,
    ) {}

    /** @return Collection<int, Plan> */
    public function execute(): Collection
    {
        return $this->planRepository->allActive();
    }
}
