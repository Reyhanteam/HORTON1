<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\DiscountCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DiscountCode> */
class DiscountCodeFactory extends Factory
{
    protected $model = DiscountCode::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('DISC-####??')),
            'type' => DiscountType::PERCENTAGE,
            'value' => 10,
            'minimum_order_amount' => 0,
            'maximum_discount_amount' => null,
            'usage_limit' => null,
            'usage_limit_per_user' => null,
            'used_count' => 0,
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
            'metadata' => [],
        ];
    }

    public function fixed(int $amount = 100_000): static
    {
        return $this->state(fn () => [
            'type' => DiscountType::FIXED,
            'value' => $amount,
        ]);
    }
}
