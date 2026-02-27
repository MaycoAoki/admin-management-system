<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PlanResource;
use App\UseCases\ListPlans;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class PlanController extends Controller
{
    public function __construct(
        private readonly ListPlans $listPlans,
    ) {}

    #[OA\Get(
        path: '/api/v1/plans',
        operationId: 'planIndex',
        summary: 'List active subscription plans.',
        security: [['sanctum' => []]],
        tags: ['Plans'],
        responses: [
            new OA\Response(response: 200, description: 'Plan collection.', content: new OA\JsonContent(ref: '#/components/schemas/PlanCollectionResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        return PlanResource::collection($this->listPlans->execute());
    }
}
