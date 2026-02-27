<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\InitiatePaymentData;
use App\Enums\PaymentMethodType;
use App\Http\Controllers\Controller;
use App\Http\Requests\InitiatePaymentRequest;
use App\Http\Resources\V1\PaymentResource;
use App\UseCases\GetPaymentDetail;
use App\UseCases\InitiatePayment;
use App\UseCases\ListPayments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class PaymentController extends Controller
{
    public function __construct(
        private readonly InitiatePayment $initiatePayment,
        private readonly ListPayments $listPayments,
        private readonly GetPaymentDetail $getPaymentDetail,
    ) {}

    #[OA\Post(
        path: '/api/v1/invoices/{id}/payments',
        operationId: 'paymentStore',
        summary: 'Create a payment for a specific invoice.',
        security: [['sanctum' => []]],
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                examples: [
                    new OA\Examples(
                        example: 'credit_card',
                        summary: 'Stored credit card',
                        value: [
                            'method' => 'credit_card',
                            'payment_method_id' => 7,
                            'amount_in_cents' => 14900,
                        ]
                    ),
                    new OA\Examples(
                        example: 'pix',
                        summary: 'PIX charge',
                        value: [
                            'method' => 'pix',
                            'amount_in_cents' => 14900,
                        ]
                    ),
                    new OA\Examples(
                        example: 'boleto',
                        summary: 'Boleto issue',
                        value: [
                            'method' => 'boleto',
                        ]
                    ),
                ],
                required: ['method'],
                properties: [
                    new OA\Property(property: 'method', type: 'string', enum: ['credit_card', 'debit_card', 'pix', 'boleto', 'bank_debit']),
                    new OA\Property(property: 'payment_method_id', type: 'integer', nullable: true, example: 7),
                    new OA\Property(property: 'amount_in_cents', type: 'integer', nullable: true, minimum: 1, example: 14900),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Payment created.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/PaymentDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'credit_card_success',
                            summary: 'Successful card payment',
                            value: [
                                'data' => [
                                    'id' => 88,
                                    'amount_in_cents' => 14900,
                                    'amount_formatted' => '$149.00',
                                    'status' => 'succeeded',
                                    'payment_method_type' => 'credit_card',
                                    'gateway' => 'stub',
                                    'pix_qr_code' => null,
                                    'pix_expires_at' => null,
                                    'boleto_url' => null,
                                    'boleto_barcode' => null,
                                    'boleto_expires_at' => null,
                                    'failure_reason' => null,
                                    'paid_at' => '2026-02-27T12:05:00Z',
                                    'failed_at' => null,
                                    'invoice' => [
                                        'id' => 45,
                                        'invoice_number' => 'INV-2026-0001',
                                        'status' => 'open',
                                        'amount_in_cents' => 14900,
                                        'amount_paid_in_cents' => 0,
                                        'amount_due_in_cents' => 14900,
                                        'amount_formatted' => '$149.00',
                                        'currency' => 'USD',
                                        'description' => 'March subscription',
                                        'due_date' => '2026-03-05',
                                        'paid_at' => null,
                                        'period_start' => '2026-03-01',
                                        'period_end' => '2026-03-31',
                                        'is_overdue' => false,
                                        'created_at' => '2026-02-27T12:00:00Z',
                                    ],
                                    'created_at' => '2026-02-27T12:00:00Z',
                                ],
                            ]
                        ),
                        new OA\Examples(
                            example: 'pix_pending',
                            summary: 'PIX awaiting payment',
                            value: [
                                'data' => [
                                    'id' => 89,
                                    'amount_in_cents' => 14900,
                                    'amount_formatted' => '$149.00',
                                    'status' => 'pending',
                                    'payment_method_type' => 'pix',
                                    'gateway' => 'stub',
                                    'pix_qr_code' => '000201010212...',
                                    'pix_expires_at' => '2026-02-27T12:30:00Z',
                                    'boleto_url' => null,
                                    'boleto_barcode' => null,
                                    'boleto_expires_at' => null,
                                    'failure_reason' => null,
                                    'paid_at' => null,
                                    'failed_at' => null,
                                    'invoice' => [
                                        'id' => 45,
                                        'invoice_number' => 'INV-2026-0001',
                                        'status' => 'open',
                                        'amount_in_cents' => 14900,
                                        'amount_paid_in_cents' => 0,
                                        'amount_due_in_cents' => 14900,
                                        'amount_formatted' => '$149.00',
                                        'currency' => 'USD',
                                        'description' => 'March subscription',
                                        'due_date' => '2026-03-05',
                                        'paid_at' => null,
                                        'period_start' => '2026-03-01',
                                        'period_end' => '2026-03-31',
                                        'is_overdue' => false,
                                        'created_at' => '2026-02-27T12:00:00Z',
                                    ],
                                    'created_at' => '2026-02-27T12:00:00Z',
                                ],
                            ]
                        ),
                        new OA\Examples(
                            example: 'boleto_pending',
                            summary: 'Boleto generated',
                            value: [
                                'data' => [
                                    'id' => 90,
                                    'amount_in_cents' => 14900,
                                    'amount_formatted' => '$149.00',
                                    'status' => 'pending',
                                    'payment_method_type' => 'boleto',
                                    'gateway' => 'stub',
                                    'pix_qr_code' => null,
                                    'pix_expires_at' => null,
                                    'boleto_url' => 'https://example.com/boleto/90',
                                    'boleto_barcode' => '34191.79001 01043.510047 91020.150008 2 12340000014900',
                                    'boleto_expires_at' => '2026-03-05T23:59:59Z',
                                    'failure_reason' => null,
                                    'paid_at' => null,
                                    'failed_at' => null,
                                    'invoice' => [
                                        'id' => 45,
                                        'invoice_number' => 'INV-2026-0001',
                                        'status' => 'open',
                                        'amount_in_cents' => 14900,
                                        'amount_paid_in_cents' => 0,
                                        'amount_due_in_cents' => 14900,
                                        'amount_formatted' => '$149.00',
                                        'currency' => 'USD',
                                        'description' => 'March subscription',
                                        'due_date' => '2026-03-05',
                                        'paid_at' => null,
                                        'period_start' => '2026-03-01',
                                        'period_end' => '2026-03-31',
                                        'is_overdue' => false,
                                        'created_at' => '2026-02-27T12:00:00Z',
                                    ],
                                    'created_at' => '2026-02-27T12:00:00Z',
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 403, description: 'Invoice does not belong to the authenticated user.', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 404, description: 'Invoice not found.', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')),
            new OA\Response(response: 422, description: 'Validation or business rule error, such as a non-payable invoice or invalid amount.', content: new OA\JsonContent(ref: '#/components/schemas/BusinessRuleError')),
        ]
    )]
    public function store(InitiatePaymentRequest $request, int $invoiceId): JsonResponse
    {
        $method = PaymentMethodType::from($request->string('method')->toString());

        $data = new InitiatePaymentData(
            methodType: $method,
            amountInCents: $request->has('amount_in_cents') ? $request->integer('amount_in_cents') : null,
            paymentMethodId: $request->filled('payment_method_id') ? $request->integer('payment_method_id') : null,
        );

        $payment = $this->initiatePayment->execute(
            invoiceId: $invoiceId,
            userId: $request->user()->id,
            data: $data,
        );

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/payments',
        operationId: 'paymentIndex',
        summary: 'List payments for the authenticated user.',
        security: [['sanctum' => []]],
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1), example: 15),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Payment collection.', content: new OA\JsonContent(ref: '#/components/schemas/PaymentCollectionResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $payments = $this->listPayments->execute(
            userId: $request->user()->id,
            perPage: (int) $request->integer('per_page', 15),
        );

        return PaymentResource::collection($payments);
    }

    #[OA\Get(
        path: '/api/v1/payments/{id}',
        operationId: 'paymentShow',
        summary: 'Show a single payment.',
        security: [['sanctum' => []]],
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment details.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/PaymentDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'successful_card_payment',
                            summary: 'Successful card payment',
                            value: [
                                'data' => [
                                    'id' => 88,
                                    'amount_in_cents' => 14900,
                                    'amount_formatted' => '$149.00',
                                    'status' => 'succeeded',
                                    'payment_method_type' => 'credit_card',
                                    'gateway' => 'stub',
                                    'pix_qr_code' => null,
                                    'pix_expires_at' => null,
                                    'boleto_url' => null,
                                    'boleto_barcode' => null,
                                    'boleto_expires_at' => null,
                                    'failure_reason' => null,
                                    'paid_at' => '2026-02-27T12:05:00Z',
                                    'failed_at' => null,
                                    'invoice' => [
                                        'id' => 45,
                                        'invoice_number' => 'INV-2026-0001',
                                        'status' => 'paid',
                                        'amount_in_cents' => 14900,
                                        'amount_paid_in_cents' => 14900,
                                        'amount_due_in_cents' => 0,
                                        'amount_formatted' => '$149.00',
                                        'currency' => 'USD',
                                        'description' => 'March subscription',
                                        'due_date' => '2026-03-05',
                                        'paid_at' => '2026-02-27T12:05:00Z',
                                        'period_start' => '2026-03-01',
                                        'period_end' => '2026-03-31',
                                        'is_overdue' => false,
                                        'created_at' => '2026-02-27T12:00:00Z',
                                    ],
                                    'created_at' => '2026-02-27T12:00:00Z',
                                ],
                            ]
                        ),
                        new OA\Examples(
                            example: 'pix_pending_payment',
                            summary: 'Pending PIX payment',
                            value: [
                                'data' => [
                                    'id' => 89,
                                    'amount_in_cents' => 14900,
                                    'amount_formatted' => '$149.00',
                                    'status' => 'pending',
                                    'payment_method_type' => 'pix',
                                    'gateway' => 'stub',
                                    'pix_qr_code' => '000201010212...',
                                    'pix_expires_at' => '2026-02-27T12:30:00Z',
                                    'boleto_url' => null,
                                    'boleto_barcode' => null,
                                    'boleto_expires_at' => null,
                                    'failure_reason' => null,
                                    'paid_at' => null,
                                    'failed_at' => null,
                                    'invoice' => [
                                        'id' => 45,
                                        'invoice_number' => 'INV-2026-0001',
                                        'status' => 'open',
                                        'amount_in_cents' => 14900,
                                        'amount_paid_in_cents' => 0,
                                        'amount_due_in_cents' => 14900,
                                        'amount_formatted' => '$149.00',
                                        'currency' => 'USD',
                                        'description' => 'March subscription',
                                        'due_date' => '2026-03-05',
                                        'paid_at' => null,
                                        'period_start' => '2026-03-01',
                                        'period_end' => '2026-03-31',
                                        'is_overdue' => false,
                                        'created_at' => '2026-02-27T12:00:00Z',
                                    ],
                                    'created_at' => '2026-02-27T12:00:00Z',
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 403, description: 'Payment does not belong to the authenticated user.', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 404, description: 'Payment not found.', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')),
        ]
    )]
    public function show(Request $request, int $id): PaymentResource
    {
        $payment = $this->getPaymentDetail->execute(
            paymentId: $id,
            userId: $request->user()->id,
        );

        return new PaymentResource($payment);
    }
}
