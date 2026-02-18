<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHouseholdRequest;
use App\Http\Requests\UpdateHouseholdRequest;
use App\Http\Resources\HouseholdResource;
use App\Models\Household;
use App\Services\HouseholdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HouseholdController extends Controller
{
    public function index(Request $request, HouseholdService $service): AnonymousResourceCollection
    {
        $filters = $request->only(['search', 'block', 'no']);
        $perPage = $request->query('per_page', 10);
        $disablePagination = filter_var($request->query('disable_pagination', false), FILTER_VALIDATE_BOOLEAN);

        $households = $service->getHouseholds($filters, (int) $perPage, $disablePagination);

        return HouseholdResource::collection($households);
    }

    public function store(StoreHouseholdRequest $request, HouseholdService $service): JsonResponse
    {
        $household = $service->createHousehold($request->validated());

        return new HouseholdResource($household)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Household $household): HouseholdResource
    {
        return new HouseholdResource($household);
    }

    public function update(UpdateHouseholdRequest $request, Household $household, HouseholdService $service): HouseholdResource
    {
        $household = $service->updateHousehold($household, $request->validated());

        return new HouseholdResource($household);
    }

    public function destroy(Household $household, HouseholdService $service): JsonResponse
    {
        $service->deleteHousehold($household);

        return response()->json(['message' => 'Household deleted successfully.']);
    }
}
