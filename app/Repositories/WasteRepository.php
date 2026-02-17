<?php

namespace App\Repositories;

use App\Models\Waste;
use App\Models\WasteElectronic;
use App\Models\WasteOrganic;
use App\Models\WastePaper;
use App\Models\WastePlastic;
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
     * @param  array<string, mixed>  $data
     */
    public function create(string $type, array $data): Waste
    {
        $modelClass = $this->resolveModelClass($type);

        return $modelClass::create($data);
    }
}
