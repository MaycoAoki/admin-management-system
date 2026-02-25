<?php

namespace App\UseCases;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class GetInvoiceDetail
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    /**
     * @throws ModelNotFoundException
     * @throws AuthorizationException
     */
    public function execute(int $invoiceId, int $userId): Invoice
    {
        $invoice = $this->invoiceRepository->findByIdWithRelations($invoiceId);

        if (! $invoice) {
            throw new ModelNotFoundException;
        }

        if ($invoice->user_id !== $userId) {
            throw new AuthorizationException;
        }

        return $invoice;
    }
}
