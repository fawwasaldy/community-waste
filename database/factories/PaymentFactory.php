<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Household;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'payment_date' => fake()->date(),
            'status' => PaymentStatus::Pending->value,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid->value,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Failed->value,
        ]);
    }
}
