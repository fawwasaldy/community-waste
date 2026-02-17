<?php

namespace Database\Factories;

use App\Enums\WasteStatus;
use App\Models\Household;
use App\Models\Waste;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Waste>
 */
class WasteFactory extends Factory
{
    protected $model = Waste::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'pickup_date' => null,
            'status' => WasteStatus::Pending->value,
        ];
    }
}
