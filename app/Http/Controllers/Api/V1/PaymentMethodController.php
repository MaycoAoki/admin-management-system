<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddPaymentMethodRequest;
use App\Http\Resources\V1\PaymentMethodResource;
use App\UseCases\AddPaymentMethod;
use App\UseCases\GetPaymentMethodDetail;
use App\UseCases\ListPaymentMethods;
use App\UseCases\RemovePaymentMethod;
use App\UseCases\SetDefaultPaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class PaymentMethodController extends Controller
{
    public function __construct(
        private readonly ListPaymentMethods $listPaymentMethods,
        private readonly AddPaymentMethod $addPaymentMethod,
        private readonly GetPaymentMethodDetail $getPaymentMethodDetail,
        private readonly RemovePaymentMethod $removePaymentMethod,
        private readonly SetDefaultPaymentMethod $setDefaultPaymentMethod,
    ) {}

    #[OA\Get(
        path: '/api/v1/payment-methods',
        operationId: 'paymentMethodIndex',
        summary: 'List stored payment methods.',
        security: [['sanctum' => []]],
        tags: ['Payment Methods'],
        responses: [
            new OA\Response(response: 200, description: 'Payment method collection.', content: new OA\JsonContent(ref: '#/components/schemas/PaymentMethodCollectionResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $methods = $this->listPaymentMethods->execute(
            userId: $request->user()->id,
        );

        return PaymentMethodResource::collection($methods);
    }

    #[OA\Post(
        path: '/api/v1/payment-methods',
        operationId: 'paymentMethodStore',
        summary: 'Store a new payment method.',
        security: [['sanctum' => []]],
        tags: ['Payment Methods'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                examples: [
                    new OA\Examples(
                        example: 'credit_card',
                        summary: 'Credit card method',
                        value: [
                            'type' => 'credit_card',
                            'last_four' => '4242',
                            'brand' => 'visa',
                            'expiry_month' => 12,
                            'expiry_year' => 2028,
                            'holder_name' => 'Jane Doe',
                        ]
                    ),
                    new OA\Examples(
                        example: 'pix',
                        summary: 'PIX key method',
                        value: [
                            'type' => 'pix',
                            'pix_key' => 'billing@example.com',
                        ]
                    ),
                    new OA\Examples(
                        example: 'bank_debit',
                        summary: 'Bank debit method',
                        value: [
                            'type' => 'bank_debit',
                            'holder_name' => 'Jane Doe',
                            'bank_name' => 'ACME Bank',
                        ]
                    ),
                ],
                required: ['type'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['credit_card', 'debit_card', 'pix', 'boleto', 'bank_debit']),
                    new OA\Property(property: 'last_four', type: 'string', nullable: true, example: '4242'),
                    new OA\Property(property: 'brand', type: 'string', nullable: true, example: 'visa'),
                    new OA\Property(property: 'expiry_month', type: 'integer', nullable: true, example: 12),
                    new OA\Property(property: 'expiry_year', type: 'integer', nullable: true, example: 2028),
                    new OA\Property(property: 'holder_name', type: 'string', nullable: true, example: 'Jane Doe'),
                    new OA\Property(property: 'pix_key', type: 'string', nullable: true, example: 'billing@example.com'),
                    new OA\Property(property: 'bank_name', type: 'string', nullable: true, example: 'ACME Bank'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Payment method created.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/PaymentMethodDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'created_card',
                            summary: 'Card created',
                            value: [
                                'data' => [
                                    'id' => 7,
                                    'type' => 'credit_card',
                                    'is_default' => true,
                                    'brand' => 'visa',
                                    'last_four' => '4242',
                                    'expiry_month' => 12,
                                    'expiry_year' => 2028,
                                    'holder_name' => 'Jane Doe',
                                    'pix_key' => null,
                                    'bank_name' => null,
                                    'created_at' => '2026-02-27T12:00:00Z',
                                ],
                            ]
                        ),
                        new OA\Examples(
                            example: 'created_pix',
                            summary: 'PIX method created',
                            value: [
                                'data' => [
                                    'id' => 8,
                                    'type' => 'pix',
                                    'is_default' => false,
                                    'brand' => null,
                                    'last_four' => null,
                                    'expiry_month' => null,
                                    'expiry_year' => null,
                                    'holder_name' => null,
                                    'pix_key' => 'billing@example.com',
                                    'bank_name' => null,
                                    'created_at' => '2026-02-27T12:01:00Z',
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 422, description: 'Validation error.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(AddPaymentMethodRequest $request): JsonResponse
    {
        $method = $this->addPaymentMethod->execute(
            userId: $request->user()->id,
            attributes: $request->validated(),
        );

        return (new PaymentMethodResource($method))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/payment-methods/{id}',
        operationId: 'paymentMethodShow',
        summary: 'Show a single payment method.',
        security: [['sanctum' => []]],
        tags: ['Payment Methods'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment method details.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/PaymentMethodDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'card_method',
                            summary: 'Stored card',
                            value: [
                                'data' => [
                                    'id' => 7,
                                    'type' => 'credit_card',
                                    'is_default' => true,
                                    'brand' => 'visa',
                                    'last_four' => '4242',
                                    'expiry_month' => 12,
                                    'expiry_year' => 2028,
                                    'holder_name' => 'Jane Doe',
                                    'pix_key' => null,
                                    'bank_name' => null,
                                    'created_at' => '2026-02-27T12:00:00Z',
                                ],
                            ]
                        ),
                        new OA\Examples(
                            example: 'bank_debit_method',
                            summary: 'Stored bank debit',
                            value: [
                                'data' => [
                                    'id' => 9,
                                    'type' => 'bank_debit',
                                    'is_default' => false,
                                    'brand' => null,
                                    'last_four' => null,
                                    'expiry_month' => null,
                                    'expiry_year' => null,
                                    'holder_name' => 'Jane Doe',
                                    'pix_key' => null,
                                    'bank_name' => 'ACME Bank',
                                    'created_at' => '2026-02-27T12:02:00Z',
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 403, description: 'Payment method does not belong to the authenticated user.', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 404, description: 'Payment method not found.', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')),
        ]
    )]
    public function show(Request $request, int $id): PaymentMethodResource
    {
        $method = $this->getPaymentMethodDetail->execute(
            paymentMethodId: $id,
            userId: $request->user()->id,
        );

        return new PaymentMethodResource($method);
    }

    #[OA\Delete(
        path: '/api/v1/payment-methods/{id}',
        operationId: 'paymentMethodDestroy',
        summary: 'Delete a stored payment method.',
        security: [['sanctum' => []]],
        tags: ['Payment Methods'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Payment method removed.'),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 403, description: 'Payment method does not belong to the authenticated user.', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 404, description: 'Payment method not found.', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')),
            new OA\Response(response: 422, description: 'Validation or business rule error, such as a payment method with pending payments.', content: new OA\JsonContent(ref: '#/components/schemas/BusinessRuleError')),
        ]
    )]
    public function destroy(Request $request, int $id): Response
    {
        $this->removePaymentMethod->execute(
            paymentMethodId: $id,
            userId: $request->user()->id,
        );

        return response()->noContent();
    }

    #[OA\Patch(
        path: '/api/v1/payment-methods/{id}/default',
        operationId: 'paymentMethodSetDefault',
        summary: 'Mark a payment method as the default one.',
        security: [['sanctum' => []]],
        tags: ['Payment Methods'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Default payment method updated.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/PaymentMethodDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'default_card_method',
                            summary: 'Card set as default',
                            value: [
                                'data' => [
                                    'id' => 7,
                                    'type' => 'credit_card',
                                    'is_default' => true,
                                    'brand' => 'visa',
                                    'last_four' => '4242',
                                    'expiry_month' => 12,
                                    'expiry_year' => 2028,
                                    'holder_name' => 'Jane Doe',
                                    'pix_key' => null,
                                    'bank_name' => null,
                                    'created_at' => '2026-02-27T12:00:00Z',
                                ],
                            ]
                        ),
                        new OA\Examples(
                            example: 'default_bank_debit_method',
                            summary: 'Bank debit set as default',
                            value: [
                                'data' => [
                                    'id' => 9,
                                    'type' => 'bank_debit',
                                    'is_default' => true,
                                    'brand' => null,
                                    'last_four' => null,
                                    'expiry_month' => null,
                                    'expiry_year' => null,
                                    'holder_name' => 'Jane Doe',
                                    'pix_key' => null,
                                    'bank_name' => 'ACME Bank',
                                    'created_at' => '2026-02-27T12:02:00Z',
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 403, description: 'Payment method does not belong to the authenticated user.', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 404, description: 'Payment method not found.', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')),
        ]
    )]
    public function setDefault(Request $request, int $id): PaymentMethodResource
    {
        $method = $this->setDefaultPaymentMethod->execute(
            paymentMethodId: $id,
            userId: $request->user()->id,
        );

        return new PaymentMethodResource($method);
    }
}
