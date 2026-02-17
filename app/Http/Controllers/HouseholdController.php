<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHouseholdRequest;
use App\Http\Requests\UpdateHouseholdRequest;
use App\Http\Resources\HouseholdResource;
use App\Models\Household;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HouseholdController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Household::class);

        $query = Household::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('owner_name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('block', 'like', "%{$search}%")
                    ->orWhere('no', 'like', "%{$search}%");
            });
        }

        if ($block = $request->query('block')) {
            $query->where('block', $block);
        }

        if ($no = $request->query('no')) {
            $query->where('no', $no);
        }

        $perPage = $request->query('per_page', 10);

        return HouseholdResource::collection($query->paginate($perPage));
    }

    public function store(StoreHouseholdRequest $request): JsonResponse
    {
        $this->authorize('create', Household::class);

        $household = Household::create($request->validated());

        return new HouseholdResource($household)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Household $household): HouseholdResource
    {
        $this->authorize('view', $household);

        return new HouseholdResource($household);
    }

    public function update(UpdateHouseholdRequest $request, Household $household): HouseholdResource
    {
        $this->authorize('update', $household);

        $household->update($request->validated());

        return new HouseholdResource($household);
    }

    public function destroy(Household $household): JsonResponse
    {
        $this->authorize('delete', $household);

        $household->delete();

        return response()->json(['message' => 'Household deleted successfully.']);
    }
}
