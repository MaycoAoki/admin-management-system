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

class DisputeController extends Controller
{
    public function __construct(
        private readonly OpenDispute $openDispute,
        private readonly ListDisputes $listDisputes,
        private readonly GetDisputeDetail $getDisputeDetail,
        private readonly WithdrawDispute $withdrawDispute,
    ) {}

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

    public function show(Request $request, int $id): DisputeResource
    {
        $dispute = $this->getDisputeDetail->execute(
            disputeId: $id,
            userId: $request->user()->id,
        );

        return new DisputeResource($dispute);
    }

    public function destroy(Request $request, int $id): DisputeResource
    {
        $dispute = $this->withdrawDispute->execute(
            disputeId: $id,
            userId: $request->user()->id,
        );

        return new DisputeResource($dispute);
    }
}
