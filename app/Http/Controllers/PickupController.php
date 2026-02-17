<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePickupRequest;
use App\Http\Resources\WasteResource;
use App\Services\WasteService;
use Illuminate\Http\JsonResponse;

class PickupController extends Controller
{
    public function store(StorePickupRequest $request, WasteService $service): JsonResponse
    {
        $waste = $service->createPickup($request->validated());

        return (new WasteResource($waste))
            ->response()
            ->setStatusCode(201);
    }
}
