<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePlanRequest;
use App\Http\Requests\SubscribeToPlanRequest;
use App\Http\Resources\V1\SubscriptionResource;
use App\UseCases\CancelSubscription;
use App\UseCases\ChangePlan;
use App\UseCases\GetCurrentSubscription;
use App\UseCases\SubscribeToPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly GetCurrentSubscription $getCurrentSubscription,
        private readonly SubscribeToPlan $subscribeToPlan,
        private readonly ChangePlan $changePlan,
        private readonly CancelSubscription $cancelSubscription,
    ) {}

    public function show(Request $request): SubscriptionResource
    {
        $subscription = $this->getCurrentSubscription->execute(
            userId: $request->user()->id,
        );

        return new SubscriptionResource($subscription);
    }

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

    public function changePlan(ChangePlanRequest $request): SubscriptionResource
    {
        $subscription = $this->changePlan->execute(
            userId: $request->user()->id,
            planId: $request->integer('plan_id'),
        );

        return new SubscriptionResource($subscription);
    }

    public function cancel(Request $request): SubscriptionResource
    {
        $subscription = $this->cancelSubscription->execute(
            userId: $request->user()->id,
        );

        return new SubscriptionResource($subscription);
    }
}
