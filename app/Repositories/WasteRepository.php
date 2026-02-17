<?php

namespace App\Repositories;

use App\Models\Waste;
use App\Models\WasteElectronic;
use App\Models\WasteOrganic;
use App\Models\WastePaper;
use App\Models\WastePlastic;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;

class WasteRepository
{
    /** @var array<string, class-string<Waste>> */
    private const array MODEL_MAP = [
        'organic' => WasteOrganic::class,
        'plastic' => WastePlastic::class,
        'paper' => WastePaper::class,
        'electronic' => WasteElectronic::class,
    ];

    /**
     * @return class-string<Waste>
     */
    public function resolveModelClass(string $type): string
    {
        return self::MODEL_MAP[$type] ?? throw new InvalidArgumentException("Unknown waste type: {$type}");
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters = []): Builder
    {
        $query = Waste::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['household_id'])) {
            $query->where('household_id', $filters['household_id']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(string $type, array $data): Waste
    {
        $modelClass = $this->resolveModelClass($type);

        return $modelClass::create($data);
    }
}
