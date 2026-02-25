<?php

namespace App\UseCases;

use App\Models\Dispute;
use App\Repositories\Contracts\DisputeRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetDisputeDetail
{
    public function __construct(
        private readonly DisputeRepositoryInterface $disputeRepository,
    ) {}

    /**
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function execute(int $disputeId, int $userId): Dispute
    {
        $dispute = $this->disputeRepository->findByIdWithPayment($disputeId);

        if (! $dispute) {
            throw new ModelNotFoundException;
        }

        if ($dispute->user_id !== $userId) {
            throw new AuthorizationException;
        }

        return $dispute;
    }
}
