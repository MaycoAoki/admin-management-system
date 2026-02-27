<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePlanRequest;
use App\Http\Requests\SubscribeToPlanRequest;
use App\Http\Requests\UpdateAutoPayRequest;
use App\Http\Requests\UpdateAutoRenewRequest;
use App\Http\Resources\V1\SubscriptionResource;
use App\UseCases\CancelSubscription;
use App\UseCases\ChangePlan;
use App\UseCases\GetCurrentSubscription;
use App\UseCases\SubscribeToPlan;
use App\UseCases\UpdateSubscriptionAutoPay;
use App\UseCases\UpdateSubscriptionAutoRenew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly GetCurrentSubscription $getCurrentSubscription,
        private readonly SubscribeToPlan $subscribeToPlan,
        private readonly ChangePlan $changePlan,
        private readonly UpdateSubscriptionAutoPay $updateSubscriptionAutoPay,
        private readonly UpdateSubscriptionAutoRenew $updateSubscriptionAutoRenew,
        private readonly CancelSubscription $cancelSubscription,
    ) {}

    #[OA\Get(
        path: '/api/v1/subscription',
        operationId: 'subscriptionShow',
        summary: 'Return the current subscription.',
        security: [['sanctum' => []]],
        tags: ['Subscriptions'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Subscription details.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SubscriptionDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'active_subscription',
                            summary: 'Active subscription',
                            value: [
                                'data' => [
                                    'id' => 10,
                                    'status' => 'active',
                                    'auto_renew' => true,
                                    'auto_pay' => true,
                                    'current_period_start' => '2026-02-27T12:00:00Z',
                                    'current_period_end' => '2026-03-27T12:00:00Z',
                                    'trial_ends_at' => null,
                                    'canceled_at' => null,
                                    'cancel_at' => null,
                                    'plan' => [
                                        'id' => 2,
                                        'name' => 'Pro',
                                        'slug' => 'pro',
                                        'billing_cycle' => 'monthly',
                                        'price_in_cents' => 9900,
                                        'price_formatted' => '$99.00',
                                        'currency' => 'USD',
                                        'trial_days' => 14,
                                    ],
                                ],
                            ]
                        ),
                        new OA\Examples(
                            example: 'past_due_subscription',
                            summary: 'Past due subscription',
                            value: [
                                'data' => [
                                    'id' => 10,
                                    'status' => 'past_due',
                                    'auto_renew' => true,
                                    'auto_pay' => false,
                                    'current_period_start' => '2026-02-27T12:00:00Z',
                                    'current_period_end' => '2026-03-27T12:00:00Z',
                                    'trial_ends_at' => null,
                                    'canceled_at' => null,
                                    'cancel_at' => null,
                                    'plan' => [
                                        'id' => 2,
                                        'name' => 'Pro',
                                        'slug' => 'pro',
                                        'billing_cycle' => 'monthly',
                                        'price_in_cents' => 9900,
                                        'price_formatted' => '$99.00',
                                        'currency' => 'USD',
                                        'trial_days' => 14,
                                    ],
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 404, description: 'No active subscription found.', content: new OA\JsonContent(ref: '#/components/schemas/NotFoundError')),
        ]
    )]
    public function show(Request $request): SubscriptionResource
    {
        $subscription = $this->getCurrentSubscription->execute(
            userId: $request->user()->id,
        );

        return new SubscriptionResource($subscription);
    }

    #[OA\Post(
        path: '/api/v1/subscription',
        operationId: 'subscriptionStore',
        summary: 'Create a subscription for the authenticated user.',
        security: [['sanctum' => []]],
        tags: ['Subscriptions'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                examples: [
                    new OA\Examples(
                        example: 'subscribe_pro',
                        summary: 'Subscribe to Pro',
                        value: [
                            'plan_id' => 2,
                        ]
                    ),
                    new OA\Examples(
                        example: 'subscribe_enterprise',
                        summary: 'Subscribe to Enterprise',
                        value: [
                            'plan_id' => 3,
                        ]
                    ),
                ],
                required: ['plan_id'],
                properties: [
                    new OA\Property(property: 'plan_id', type: 'integer', example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Subscription created.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SubscriptionDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'trial_subscription_created',
                            summary: 'Trial subscription created',
                            value: [
                                'data' => [
                                    'id' => 10,
                                    'status' => 'trialing',
                                    'auto_renew' => true,
                                    'auto_pay' => false,
                                    'current_period_start' => '2026-02-27T12:00:00Z',
                                    'current_period_end' => '2026-03-27T12:00:00Z',
                                    'trial_ends_at' => '2026-03-13T12:00:00Z',
                                    'canceled_at' => null,
                                    'cancel_at' => null,
                                    'plan' => [
                                        'id' => 2,
                                        'name' => 'Pro',
                                        'slug' => 'pro',
                                        'billing_cycle' => 'monthly',
                                        'price_in_cents' => 9900,
                                        'price_formatted' => '$99.00',
                                        'currency' => 'USD',
                                        'trial_days' => 14,
                                    ],
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 422, description: 'Validation or business rule error, such as an existing active subscription.', content: new OA\JsonContent(ref: '#/components/schemas/BusinessRuleError')),
        ]
    )]
    public function store(SubscribeToPlanRequest $request): JsonResponse
    {
        $subscription = $this->subscribeToPlan->execute(
            userId: $request->user()->id,
            planId: $request->integer('plan_id'),
        );

        return (new SubscriptionResource($subscription))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Patch(
        path: '/api/v1/subscription/plan',
        operationId: 'subscriptionChangePlan',
        summary: 'Change the current subscription plan.',
        security: [['sanctum' => []]],
        tags: ['Subscriptions'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                examples: [
                    new OA\Examples(
                        example: 'upgrade_to_enterprise',
                        summary: 'Upgrade plan',
                        value: [
                            'plan_id' => 3,
                        ]
                    ),
                    new OA\Examples(
                        example: 'downgrade_to_starter',
                        summary: 'Downgrade plan',
                        value: [
                            'plan_id' => 1,
                        ]
                    ),
                ],
                required: ['plan_id'],
                properties: [
                    new OA\Property(property: 'plan_id', type: 'integer', example: 3),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Subscription updated.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SubscriptionDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'subscription_upgraded',
                            summary: 'Plan changed successfully',
                            value: [
                                'data' => [
                                    'id' => 10,
                                    'status' => 'active',
                                    'auto_renew' => true,
                                    'auto_pay' => true,
                                    'current_period_start' => '2026-02-27T12:00:00Z',
                                    'current_period_end' => '2026-03-27T12:00:00Z',
                                    'trial_ends_at' => null,
                                    'canceled_at' => null,
                                    'cancel_at' => null,
                                    'plan' => [
                                        'id' => 3,
                                        'name' => 'Enterprise',
                                        'slug' => 'enterprise',
                                        'billing_cycle' => 'monthly',
                                        'price_in_cents' => 29900,
                                        'price_formatted' => '$299.00',
                                        'currency' => 'USD',
                                        'trial_days' => 0,
                                    ],
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 422, description: 'Validation or business rule error, such as reusing the same plan or missing an active subscription.', content: new OA\JsonContent(ref: '#/components/schemas/BusinessRuleError')),
        ]
    )]
    public function changePlan(ChangePlanRequest $request): SubscriptionResource
    {
        $subscription = $this->changePlan->execute(
            userId: $request->user()->id,
            planId: $request->integer('plan_id'),
        );

        return new SubscriptionResource($subscription);
    }

    #[OA\Patch(
        path: '/api/v1/subscription/auto-pay',
        operationId: 'subscriptionUpdateAutoPay',
        summary: 'Enable or disable automatic payment.',
        security: [['sanctum' => []]],
        tags: ['Subscriptions'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                examples: [
                    new OA\Examples(
                        example: 'enable_auto_pay',
                        summary: 'Enable auto-pay',
                        value: [
                            'auto_pay' => true,
                        ]
                    ),
                    new OA\Examples(
                        example: 'disable_auto_pay',
                        summary: 'Disable auto-pay',
                        value: [
                            'auto_pay' => false,
                        ]
                    ),
                ],
                required: ['auto_pay'],
                properties: [
                    new OA\Property(property: 'auto_pay', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Auto pay updated.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SubscriptionDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'auto_pay_enabled',
                            summary: 'Auto-pay enabled',
                            value: [
                                'data' => [
                                    'id' => 10,
                                    'status' => 'active',
                                    'auto_renew' => true,
                                    'auto_pay' => true,
                                    'current_period_start' => '2026-02-27T12:00:00Z',
                                    'current_period_end' => '2026-03-27T12:00:00Z',
                                    'trial_ends_at' => null,
                                    'canceled_at' => null,
                                    'cancel_at' => null,
                                    'plan' => [
                                        'id' => 2,
                                        'name' => 'Pro',
                                        'slug' => 'pro',
                                        'billing_cycle' => 'monthly',
                                        'price_in_cents' => 9900,
                                        'price_formatted' => '$99.00',
                                        'currency' => 'USD',
                                        'trial_days' => 14,
                                    ],
                                ],
                            ]
                        ),
                        new OA\Examples(
                            example: 'auto_pay_disabled',
                            summary: 'Auto-pay disabled',
                            value: [
                                'data' => [
                                    'id' => 10,
                                    'status' => 'active',
                                    'auto_renew' => true,
                                    'auto_pay' => false,
                                    'current_period_start' => '2026-02-27T12:00:00Z',
                                    'current_period_end' => '2026-03-27T12:00:00Z',
                                    'trial_ends_at' => null,
                                    'canceled_at' => null,
                                    'cancel_at' => null,
                                    'plan' => [
                                        'id' => 2,
                                        'name' => 'Pro',
                                        'slug' => 'pro',
                                        'billing_cycle' => 'monthly',
                                        'price_in_cents' => 9900,
                                        'price_formatted' => '$99.00',
                                        'currency' => 'USD',
                                        'trial_days' => 14,
                                    ],
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 422, description: 'Validation or business rule error, such as missing an eligible default payment method.', content: new OA\JsonContent(ref: '#/components/schemas/BusinessRuleError')),
        ]
    )]
    public function updateAutoPay(UpdateAutoPayRequest $request): SubscriptionResource
    {
        $subscription = $this->updateSubscriptionAutoPay->execute(
            userId: $request->user()->id,
            autoPay: $request->boolean('auto_pay'),
        );

        return new SubscriptionResource($subscription);
    }

    #[OA\Patch(
        path: '/api/v1/subscription/auto-renew',
        operationId: 'subscriptionUpdateAutoRenew',
        summary: 'Enable or disable automatic renewal.',
        security: [['sanctum' => []]],
        tags: ['Subscriptions'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                examples: [
                    new OA\Examples(
                        example: 'enable_auto_renew',
                        summary: 'Enable auto-renew',
                        value: [
                            'auto_renew' => true,
                        ]
                    ),
                    new OA\Examples(
                        example: 'disable_auto_renew',
                        summary: 'Disable auto-renew',
                        value: [
                            'auto_renew' => false,
                        ]
                    ),
                ],
                required: ['auto_renew'],
                properties: [
                    new OA\Property(property: 'auto_renew', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Auto renew updated.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SubscriptionDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'auto_renew_enabled',
                            summary: 'Auto-renew enabled',
                            value: [
                                'data' => [
                                    'id' => 10,
                                    'status' => 'active',
                                    'auto_renew' => true,
                                    'auto_pay' => true,
                                    'current_period_start' => '2026-02-27T12:00:00Z',
                                    'current_period_end' => '2026-03-27T12:00:00Z',
                                    'trial_ends_at' => null,
                                    'canceled_at' => null,
                                    'cancel_at' => null,
                                    'plan' => [
                                        'id' => 2,
                                        'name' => 'Pro',
                                        'slug' => 'pro',
                                        'billing_cycle' => 'monthly',
                                        'price_in_cents' => 9900,
                                        'price_formatted' => '$99.00',
                                        'currency' => 'USD',
                                        'trial_days' => 14,
                                    ],
                                ],
                            ]
                        ),
                        new OA\Examples(
                            example: 'auto_renew_disabled',
                            summary: 'Auto-renew disabled',
                            value: [
                                'data' => [
                                    'id' => 10,
                                    'status' => 'active',
                                    'auto_renew' => false,
                                    'auto_pay' => true,
                                    'current_period_start' => '2026-02-27T12:00:00Z',
                                    'current_period_end' => '2026-03-27T12:00:00Z',
                                    'trial_ends_at' => null,
                                    'canceled_at' => null,
                                    'cancel_at' => null,
                                    'plan' => [
                                        'id' => 2,
                                        'name' => 'Pro',
                                        'slug' => 'pro',
                                        'billing_cycle' => 'monthly',
                                        'price_in_cents' => 9900,
                                        'price_formatted' => '$99.00',
                                        'currency' => 'USD',
                                        'trial_days' => 14,
                                    ],
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 422, description: 'Validation or business rule error, such as missing an active subscription.', content: new OA\JsonContent(ref: '#/components/schemas/BusinessRuleError')),
        ]
    )]
    public function updateAutoRenew(UpdateAutoRenewRequest $request): SubscriptionResource
    {
        $subscription = $this->updateSubscriptionAutoRenew->execute(
            userId: $request->user()->id,
            autoRenew: $request->boolean('auto_renew'),
        );

        return new SubscriptionResource($subscription);
    }

    #[OA\Delete(
        path: '/api/v1/subscription',
        operationId: 'subscriptionCancel',
        summary: 'Cancel the current subscription.',
        security: [['sanctum' => []]],
        tags: ['Subscriptions'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Subscription canceled.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/SubscriptionDataResponse',
                    examples: [
                        new OA\Examples(
                            example: 'subscription_canceled',
                            summary: 'Cancellation scheduled',
                            value: [
                                'data' => [
                                    'id' => 10,
                                    'status' => 'canceled',
                                    'auto_renew' => false,
                                    'auto_pay' => false,
                                    'current_period_start' => '2026-02-27T12:00:00Z',
                                    'current_period_end' => '2026-03-27T12:00:00Z',
                                    'trial_ends_at' => null,
                                    'canceled_at' => '2026-02-27T12:15:00Z',
                                    'cancel_at' => '2026-03-27T12:00:00Z',
                                    'plan' => [
                                        'id' => 2,
                                        'name' => 'Pro',
                                        'slug' => 'pro',
                                        'billing_cycle' => 'monthly',
                                        'price_in_cents' => 9900,
                                        'price_formatted' => '$99.00',
                                        'currency' => 'USD',
                                        'trial_days' => 14,
                                    ],
                                ],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
            new OA\Response(response: 422, description: 'Validation or business rule error, such as missing an active subscription.', content: new OA\JsonContent(ref: '#/components/schemas/BusinessRuleError')),
        ]
    )]
    public function cancel(Request $request): SubscriptionResource
    {
        $subscription = $this->cancelSubscription->execute(
            userId: $request->user()->id,
        );

        return new SubscriptionResource($subscription);
    }
}
