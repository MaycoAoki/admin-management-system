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
use OpenApi\Attributes as OA;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly ListInvoices $listInvoices,
        private readonly GetInvoiceDetail $getInvoiceDetail,
    ) {}

    #[OA\Get(
        path: '/api/v1/invoices',
        operationId: 'invoiceIndex',
        summary: 'List invoices for the authenticated user.',
        security: [['sanctum' => []]],
        tags: ['Invoices'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1), example: 15),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['draft', 'open', 'paid', 'void', 'uncollectible'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Invoice collection.', content: new OA\JsonContent(ref: '#/components/schemas/InvoiceCollectionResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 422, description: 'Validation error.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
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

    #[OA\Get(
        path: '/api/v1/invoices/{id}',
        operationId: 'invoiceShow',
        summary: 'Show a single invoice with related details.',
        security: [['sanctum' => []]],
        tags: ['Invoices'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Invoice details.', content: new OA\JsonContent(ref: '#/components/schemas/InvoiceDataResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 403, description: 'Invoice does not belong to the authenticated user.', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 404, description: 'Invoice not found.', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')),
        ]
    )]
    public function show(Request $request, int $id): InvoiceResource
    {
        $invoice = $this->getInvoiceDetail->execute(
            invoiceId: $id,
            userId: $request->user()->id,
        );

        return new InvoiceResource($invoice);
    }
}
