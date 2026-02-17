<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchedulePickupRequest;
use App\Http\Requests\StorePickupRequest;
use App\Http\Resources\WasteResource;
use App\Services\WasteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PickupController extends Controller
{
    public function index(Request $request, WasteService $service): AnonymousResourceCollection
    {
        $filters = $request->only(['status', 'type', 'household_id']);
        $perPage = $request->query('per_page', 10);

        $pickups = $service->getPickups($filters, (int) $perPage);

        return WasteResource::collection($pickups);
    }

    public function store(StorePickupRequest $request, WasteService $service): JsonResponse
    {
        $waste = $service->createPickup($request->validated());

        return new WasteResource($waste)
            ->response()
            ->setStatusCode(201);
    }

    public function schedule(SchedulePickupRequest $request, string $id, WasteService $service): WasteResource
    {
        $waste = $service->schedulePickup($id, $request->validated()['pickup_date']);

        return new WasteResource($waste);
    }
}
