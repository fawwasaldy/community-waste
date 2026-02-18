<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchedulePickupRequest;
use App\Http\Requests\StorePickupRequest;
use App\Http\Resources\WasteResource;
use App\Services\WasteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PickupController extends Controller
{
    public function index(Request $request, WasteService $service): JsonResponse
    {
        $filters = $request->only(['status', 'type', 'household_id']);
        $perPage = $request->query('per_page', 10);
        $disablePagination = filter_var($request->query('disable_pagination', false), FILTER_VALIDATE_BOOLEAN);

        $pickups = $service->getPickups($filters, (int) $perPage, $disablePagination);

        $data = WasteResource::collection($pickups)->response()->getData(true);

        return response()->json(['message' => 'Pickups retrieved successfully.'] + $data);
    }

    public function store(StorePickupRequest $request, WasteService $service): JsonResponse
    {
        $waste = $service->createPickup($request->validated());

        $data = new WasteResource($waste)->response()->getData(true);

        return response()->json(['message' => 'Pickup created successfully.'] + $data, 201);
    }

    public function schedule(SchedulePickupRequest $request, string $id, WasteService $service): JsonResponse
    {
        $waste = $service->schedulePickup($id, $request->validated()['pickup_date']);

        $data = new WasteResource($waste)->response()->getData(true);

        return response()->json(['message' => 'Pickup scheduled successfully.'] + $data);
    }

    public function complete(string $id, WasteService $service): JsonResponse
    {
        $waste = $service->completePickup($id);

        $data = new WasteResource($waste)->response()->getData(true);

        return response()->json(['message' => 'Pickup completed successfully.'] + $data);
    }

    public function cancel(string $id, WasteService $service): JsonResponse
    {
        $waste = $service->cancelPickup($id);

        $data = new WasteResource($waste)->response()->getData(true);

        return response()->json(['message' => 'Pickup canceled successfully.'] + $data);
    }
}
