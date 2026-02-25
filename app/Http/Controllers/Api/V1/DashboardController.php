<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DashboardResource;
use App\UseCases\GetDashboardSummary;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly GetDashboardSummary $getDashboardSummary) {}

    public function show(Request $request): DashboardResource
    {
        $data = $this->getDashboardSummary->execute($request->user()->id);

        return new DashboardResource($data);
    }
}
