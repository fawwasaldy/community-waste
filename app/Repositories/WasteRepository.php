<?php

namespace App\Repositories;

use App\Enums\WasteStatus;
use App\Models\Waste;
use App\Models\WasteElectronic;
use App\Models\WasteOrganic;
use App\Models\WastePaper;
use App\Models\WastePlastic;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

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

    public function find(string $id): ?Waste
    {
        $waste = Waste::find($id);

        if (! $waste) {
            return null;
        }

        $modelClass = $this->resolveModelClass($waste->type->value);

        /** @var Waste|null */
        return $modelClass::find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(string $type, array $data): Waste
    {
        $modelClass = $this->resolveModelClass($type);

        return $modelClass::create($data);
    }

    public function updateSchedule(Waste $waste, string $pickupDate): Waste
    {
        $waste->pickup_date = $pickupDate;
        $waste->status = WasteStatus::Scheduled;
        $waste->save();

        return $waste;
    }

    public function markCompleted(Waste $waste): Waste
    {
        $waste->status = WasteStatus::Completed;
        $waste->save();

        return $waste;
    }
}
