<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Plan> */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->numberBetween(1000, 1000000),
            'currency' => 'IRR',
            'duration_value' => fake()->numberBetween(1, 30),
            'duration_unit' => 'day',
            'capacity_value' => fake()->numberBetween(1, 100),
            'capacity_unit' => 'GB',
            'is_trial' => false,
            'status' => 'active',
            'sort_order' => fake()->numberBetween(0, 100),
            'metadata' => [],
        ];
    }

    public function trial(): static
    {
        return $this->state([
            'is_trial' => true,
            'trial_duration_value' => 3,
            'trial_duration_unit' => 'day',
            'price' => 0,
        ]);
    }
}
