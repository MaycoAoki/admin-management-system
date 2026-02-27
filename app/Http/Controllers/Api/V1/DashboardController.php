<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DashboardResource;
use App\UseCases\GetDashboardSummary;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    public function __construct(private readonly GetDashboardSummary $getDashboardSummary) {}

    #[OA\Get(
        path: '/api/v1/dashboard',
        operationId: 'dashboardShow',
        summary: 'Return the current billing dashboard summary.',
        security: [['sanctum' => []]],
        tags: ['Dashboard'],
        responses: [
            new OA\Response(response: 200, description: 'Dashboard summary.', content: new OA\JsonContent(ref: '#/components/schemas/DashboardDataResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedError')),
        ]
    )]
    public function show(Request $request): DashboardResource
    {
        $data = $this->getDashboardSummary->execute($request->user()->id);

        return new DashboardResource($data);
    }
}
