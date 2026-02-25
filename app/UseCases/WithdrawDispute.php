<?php

namespace App\UseCases;

use App\Enums\DisputeStatus;
use App\Models\Dispute;
use App\Repositories\Contracts\DisputeRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class WithdrawDispute
{
    public function __construct(
        private readonly DisputeRepositoryInterface $disputeRepository,
    ) {}

    /**
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     * @throws ValidationException
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

        if (! $dispute->status->isWithdrawable()) {
            throw ValidationException::withMessages([
                'dispute' => ['Dispute cannot be withdrawn in its current status.'],
            ]);
        }

        return $this->disputeRepository->update($dispute, [
            'status' => DisputeStatus::Withdrawn,
            'withdrawn_at' => now(),
        ]);
    }
}
