<?php

namespace App\Services;

use App\Models\Household;
use App\Repositories\HouseholdRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class HouseholdService
{
    public function __construct(private HouseholdRepository $householdRepository) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getHouseholds(array $filters, int $perPage = 10, bool $disablePagination = false): Collection|LengthAwarePaginator
    {
        $query = $this->householdRepository->query($filters);

        if ($disablePagination) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createHousehold(array $data): Household
    {
        return $this->householdRepository->create($data);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findHousehold(string $id): Household
    {
        $household = $this->householdRepository->find($id);

        if (! $household) {
            throw (new ModelNotFoundException)->setModel(Household::class, $id);
        }

        return $household;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateHousehold(Household $household, array $data): Household
    {
        return $this->householdRepository->update($household, $data);
    }

    public function deleteHousehold(Household $household): void
    {
        $this->householdRepository->delete($household);
    }
}
