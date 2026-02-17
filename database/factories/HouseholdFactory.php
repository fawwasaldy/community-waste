<?php

namespace Database\Factories;

use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Household>
 */
class HouseholdFactory extends Factory
{
    protected $model = Household::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_name' => fake()->name(),
            'address' => fake()->address(),
            'block' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'no' => (string) fake()->numberBetween(1, 100),
        ];
    }
}
