<?php

namespace App\Repositories;

use App\Models\Household;
use Illuminate\Database\Eloquent\Builder;

class HouseholdRepository
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters = []): Builder
    {
        $query = Household::query();

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('owner_name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('block', 'like', "%{$search}%")
                    ->orWhere('no', 'like', "%{$search}%");
            });
        }

        if (isset($filters['block'])) {
            $query->where('block', $filters['block']);
        }

        if (isset($filters['no'])) {
            $query->where('no', $filters['no']);
        }

        return $query;
    }

    public function find(string $id): ?Household
    {
        /** @var Household|null */
        return Household::find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Household
    {
        return Household::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Household $household, array $data): Household
    {
        $household->update($data);

        return $household;
    }

    public function delete(Household $household): void
    {
        $household->delete();
    }
}
