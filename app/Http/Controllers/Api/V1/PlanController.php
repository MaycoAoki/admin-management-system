<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PlanResource;
use App\UseCases\ListPlans;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlanController extends Controller
{
    public function __construct(
        private readonly ListPlans $listPlans,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return PlanResource::collection($this->listPlans->execute());
    }
}
