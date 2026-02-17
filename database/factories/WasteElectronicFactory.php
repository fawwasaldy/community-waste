<?php

namespace Database\Factories;

use App\Models\WasteElectronic;

class WasteElectronicFactory extends WasteFactory
{
    protected $model = WasteElectronic::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return array_merge(parent::definition(), [
            'safety_check' => fake()->boolean(),
        ]);
    }
}
