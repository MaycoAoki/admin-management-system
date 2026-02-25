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

class PaymentController extends Controller
{
    public function __construct(
        private readonly InitiatePayment $initiatePayment,
        private readonly ListPayments $listPayments,
        private readonly GetPaymentDetail $getPaymentDetail,
    ) {}

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

    public function index(Request $request): AnonymousResourceCollection
    {
        $payments = $this->listPayments->execute(
            userId: $request->user()->id,
            perPage: (int) $request->integer('per_page', 15),
        );

        return PaymentResource::collection($payments);
    }

    public function show(Request $request, int $id): PaymentResource
    {
        $payment = $this->getPaymentDetail->execute(
            paymentId: $id,
            userId: $request->user()->id,
        );

        return new PaymentResource($payment);
    }
}
