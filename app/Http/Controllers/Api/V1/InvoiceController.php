<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListInvoicesRequest;
use App\Http\Resources\V1\InvoiceCollection;
use App\Http\Resources\V1\InvoiceResource;
use App\UseCases\GetInvoiceDetail;
use App\UseCases\ListInvoices;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly ListInvoices $listInvoices,
        private readonly GetInvoiceDetail $getInvoiceDetail,
    ) {}

    public function index(ListInvoicesRequest $request): InvoiceCollection
    {
        $status = $request->filled('status')
            ? InvoiceStatus::from($request->string('status')->toString())
            : null;

        $invoices = $this->listInvoices->execute(
            userId: $request->user()->id,
            perPage: (int) $request->integer('per_page', 15),
            status: $status,
        );

        return new InvoiceCollection($invoices);
    }

    public function show(Request $request, int $id): InvoiceResource
    {
        $invoice = $this->getInvoiceDetail->execute(
            invoiceId: $id,
            userId: $request->user()->id,
        );

        return new InvoiceResource($invoice);
    }
}
