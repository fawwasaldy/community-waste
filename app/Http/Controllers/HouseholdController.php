<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHouseholdRequest;
use App\Http\Requests\UpdateHouseholdRequest;
use App\Http\Resources\HouseholdResource;
use App\Models\Household;
use App\Services\HouseholdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HouseholdController extends Controller
{
    public function index(Request $request, HouseholdService $service): JsonResponse
    {
        $filters = $request->only(['search', 'block', 'no']);
        $perPage = $request->query('per_page', 10);
        $disablePagination = filter_var($request->query('disable_pagination', false), FILTER_VALIDATE_BOOLEAN);

        $households = $service->getHouseholds($filters, (int) $perPage, $disablePagination);

        $data = HouseholdResource::collection($households)->response()->getData(true);

        return response()->json(['message' => 'Households retrieved successfully.'] + $data);
    }

    public function store(StoreHouseholdRequest $request, HouseholdService $service): JsonResponse
    {
        $household = $service->createHousehold($request->validated());

        $data = new HouseholdResource($household)->response()->getData(true);

        return response()->json(['message' => 'Household created successfully.'] + $data, 201);
    }

    public function show(Household $household): JsonResponse
    {
        $data = new HouseholdResource($household)->response()->getData(true);

        return response()->json(['message' => 'Household retrieved successfully.'] + $data);
    }

    public function update(UpdateHouseholdRequest $request, Household $household, HouseholdService $service): JsonResponse
    {
        $household = $service->updateHousehold($household, $request->validated());

        $data = new HouseholdResource($household)->response()->getData(true);

        return response()->json(['message' => 'Household updated successfully.'] + $data);
    }

    public function destroy(Household $household, HouseholdService $service): JsonResponse
    {
        $service->deleteHousehold($household);

        return response()->json(['message' => 'Household deleted successfully.']);
    }
}
