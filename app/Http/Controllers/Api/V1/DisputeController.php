<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DisputeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\OpenDisputeRequest;
use App\Http\Resources\V1\DisputeResource;
use App\UseCases\GetDisputeDetail;
use App\UseCases\ListDisputes;
use App\UseCases\OpenDispute;
use App\UseCases\WithdrawDispute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class DisputeController extends Controller
{
    public function __construct(
        private readonly OpenDispute $openDispute,
        private readonly ListDisputes $listDisputes,
        private readonly GetDisputeDetail $getDisputeDetail,
        private readonly WithdrawDispute $withdrawDispute,
    ) {}

    #[OA\Post(
        path: '/api/v1/payments/{id}/disputes',
        operationId: 'disputeStore',
        summary: 'Open a dispute for a specific payment.',
        security: [['sanctum' => []]],
        tags: ['Disputes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                examples: [
                    new OA\Examples(
                        example: 'fraudulent_charge',
                        summary: 'Fraud claim',
                        value: [
                            'reason' => 'fraudulent',
                            'description' => 'Unknown charge on statement.',
                        ]
                    ),
                    new OA\Examples(
                        example: 'duplicate_charge',
                        summary: 'Duplicate charge',
                        value: [
                            'reason' => 'duplicate',
                            'description' => 'The same payment was captured twice.',
                        ]
                    ),
                ],
                required: ['reason'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string', enum: ['fraudulent', 'duplicate', 'product_not_received', 'product_not_as_described', 'unrecognized', 'other']),
                    new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 1000),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Dispute opened.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/DisputeDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'open_fraud_dispute',
                            summary: 'Open fraud dispute',
                            value: [
                                'data' => [
                                    'id' => 15,
                                    'status' => 'open',
                                    'reason' => 'fraudulent',
                                    'description' => 'Unknown charge on statement.',
                                    'gateway_dispute_id' => 'dp_12345',
                                    'resolved_at' => null,
                                    'withdrawn_at' => null,
                                    'payment' => [
                                        'id' => 88,
                                        'status' => 'succeeded',
                                        'amount_in_cents' => 9900,
                                        'amount_formatted' => '$99.00',
                                        'payment_method_type' => 'credit_card',
                                    ],
                                    'created_at' => '2026-02-27T12:10:00Z',
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 403, description: 'Payment does not belong to the authenticated user.', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 404, description: 'Payment not found.', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')),
            new OA\Response(response: 422, description: 'Validation or business rule error, such as disputing a non-succeeded payment.', content: new OA\JsonContent(ref: '#/components/schemas/BusinessRuleError')),
        ]
    )]
    public function store(OpenDisputeRequest $request, int $paymentId): JsonResponse
    {
        $dispute = $this->openDispute->execute(
            paymentId: $paymentId,
            userId: $request->user()->id,
            reason: $request->string('reason')->toString(),
            description: $request->string('description')->toString() ?: null,
        );

        return (new DisputeResource($dispute))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/api/v1/disputes',
        operationId: 'disputeIndex',
        summary: 'List disputes for the authenticated user.',
        security: [['sanctum' => []]],
        tags: ['Disputes'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1), example: 15),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['open', 'under_review', 'won', 'lost', 'withdrawn'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dispute collection.', content: new OA\JsonContent(ref: '#/components/schemas/DisputeCollectionResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->filled('status')
            ? DisputeStatus::tryFrom($request->string('status')->toString())
            : null;

        $disputes = $this->listDisputes->execute(
            userId: $request->user()->id,
            perPage: $request->integer('per_page', 15),
            status: $status,
        );

        return DisputeResource::collection($disputes);
    }

    #[OA\Get(
        path: '/api/v1/disputes/{id}',
        operationId: 'disputeShow',
        summary: 'Show a single dispute.',
        security: [['sanctum' => []]],
        tags: ['Disputes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dispute details.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/DisputeDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'open_dispute',
                            summary: 'Open dispute',
                            value: [
                                'data' => [
                                    'id' => 15,
                                    'status' => 'open',
                                    'reason' => 'fraudulent',
                                    'description' => 'Unknown charge on statement.',
                                    'gateway_dispute_id' => 'dp_12345',
                                    'resolved_at' => null,
                                    'withdrawn_at' => null,
                                    'payment' => [
                                        'id' => 88,
                                        'status' => 'succeeded',
                                        'amount_in_cents' => 9900,
                                        'amount_formatted' => '$99.00',
                                        'payment_method_type' => 'credit_card',
                                    ],
                                    'created_at' => '2026-02-27T12:10:00Z',
                                ],
                            ]
                        ),
                        new OA\Examples(
                            example: 'withdrawn_dispute',
                            summary: 'Withdrawn dispute',
                            value: [
                                'data' => [
                                    'id' => 15,
                                    'status' => 'withdrawn',
                                    'reason' => 'duplicate',
                                    'description' => 'The same payment was captured twice.',
                                    'gateway_dispute_id' => 'dp_12345',
                                    'resolved_at' => null,
                                    'withdrawn_at' => '2026-02-27T13:00:00Z',
                                    'payment' => [
                                        'id' => 88,
                                        'status' => 'succeeded',
                                        'amount_in_cents' => 9900,
                                        'amount_formatted' => '$99.00',
                                        'payment_method_type' => 'credit_card',
                                    ],
                                    'created_at' => '2026-02-27T12:10:00Z',
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 403, description: 'Dispute does not belong to the authenticated user.', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 404, description: 'Dispute not found.', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')),
        ]
    )]
    public function show(Request $request, int $id): DisputeResource
    {
        $dispute = $this->getDisputeDetail->execute(
            disputeId: $id,
            userId: $request->user()->id,
        );

        return new DisputeResource($dispute);
    }

    #[OA\Delete(
        path: '/api/v1/disputes/{id}',
        operationId: 'disputeDestroy',
        summary: 'Withdraw an open dispute.',
        security: [['sanctum' => []]],
        tags: ['Disputes'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dispute withdrawn.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/DisputeDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'withdraw_dispute',
                            summary: 'Withdrawn dispute',
                            value: [
                                'data' => [
                                    'id' => 15,
                                    'status' => 'withdrawn',
                                    'reason' => 'fraudulent',
                                    'description' => 'Unknown charge on statement.',
                                    'gateway_dispute_id' => 'dp_12345',
                                    'resolved_at' => null,
                                    'withdrawn_at' => '2026-02-27T13:00:00Z',
                                    'payment' => [
                                        'id' => 88,
                                        'status' => 'succeeded',
                                        'amount_in_cents' => 9900,
                                        'amount_formatted' => '$99.00',
                                        'payment_method_type' => 'credit_card',
                                    ],
                                    'created_at' => '2026-02-27T12:10:00Z',
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 403, description: 'Dispute does not belong to the authenticated user.', content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenError')),
            new OA\Response(response: 404, description: 'Dispute not found.', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')),
            new OA\Response(response: 422, description: 'Validation or business rule error, such as withdrawing a non-open dispute.', content: new OA\JsonContent(ref: '#/components/schemas/BusinessRuleError')),
        ]
    )]
    public function destroy(Request $request, int $id): DisputeResource
    {
        $dispute = $this->withdrawDispute->execute(
            disputeId: $id,
            userId: $request->user()->id,
        );

        return new DisputeResource($dispute);
    }
}
