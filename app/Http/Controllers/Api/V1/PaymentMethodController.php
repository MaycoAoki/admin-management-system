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

class PaymentMethodController extends Controller
{
    public function __construct(
        private readonly ListPaymentMethods $listPaymentMethods,
        private readonly AddPaymentMethod $addPaymentMethod,
        private readonly GetPaymentMethodDetail $getPaymentMethodDetail,
        private readonly RemovePaymentMethod $removePaymentMethod,
        private readonly SetDefaultPaymentMethod $setDefaultPaymentMethod,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $methods = $this->listPaymentMethods->execute(
            userId: $request->user()->id,
        );

        return PaymentMethodResource::collection($methods);
    }

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

    public function show(Request $request, int $id): PaymentMethodResource
    {
        $method = $this->getPaymentMethodDetail->execute(
            paymentMethodId: $id,
            userId: $request->user()->id,
        );

        return new PaymentMethodResource($method);
    }

    public function destroy(Request $request, int $id): Response
    {
        $this->removePaymentMethod->execute(
            paymentMethodId: $id,
            userId: $request->user()->id,
        );

        return response()->noContent();
    }

    public function setDefault(Request $request, int $id): PaymentMethodResource
    {
        $method = $this->setDefaultPaymentMethod->execute(
            paymentMethodId: $id,
            userId: $request->user()->id,
        );

        return new PaymentMethodResource($method);
    }
}
