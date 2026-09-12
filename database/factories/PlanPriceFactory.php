<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlanPrice> */
class PlanPriceFactory extends Factory
{
    protected $model = PlanPrice::class;

    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'currency' => 'IRR',
            'amount' => fake()->numberBetween(1000, 1000000),
            'is_default' => true,
            'starts_at' => now(),
        ];
    }
}
