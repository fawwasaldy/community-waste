<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHouseholdRequest;
use App\Http\Requests\UpdateHouseholdRequest;
use App\Http\Resources\HouseholdResource;
use App\Models\Household;
use App\Services\HouseholdService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HouseholdController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, HouseholdService $service): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Household::class);

        $filters = $request->only(['search', 'block', 'no']);
        $perPage = $request->query('per_page', 10);

        $households = $service->getHouseholds($filters, (int) $perPage);

        return HouseholdResource::collection($households);
    }

    public function store(StoreHouseholdRequest $request, HouseholdService $service): JsonResponse
    {
        $this->authorize('create', Household::class);

        $household = $service->createHousehold($request->validated());

        return new HouseholdResource($household)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Household $household): HouseholdResource
    {
        $this->authorize('view', $household);

        return new HouseholdResource($household);
    }

    public function update(UpdateHouseholdRequest $request, Household $household, HouseholdService $service): HouseholdResource
    {
        $this->authorize('update', $household);

        $household = $service->updateHousehold($household, $request->validated());

        return new HouseholdResource($household);
    }

    public function destroy(Household $household, HouseholdService $service): JsonResponse
    {
        $this->authorize('delete', $household);

        $service->deleteHousehold($household);

        return response()->json(['message' => 'Household deleted successfully.']);
    }
}
