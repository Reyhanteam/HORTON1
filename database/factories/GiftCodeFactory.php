<?php

namespace Database\Factories;

use App\Enums\GiftCodeType;
use App\Models\GiftCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GiftCode> */
class GiftCodeFactory extends Factory
{
    protected $model = GiftCode::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('GIFT-####??')),
            'type' => GiftCodeType::CREDIT,
            'value' => 100_000,
            'usage_limit' => null,
            'used_count' => 0,
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'is_active' => true,
            'metadata' => [],
        ];
    }

    public function discount(int $amount = 100_000): static
    {
        return $this->state(fn () => [
            'type' => GiftCodeType::DISCOUNT,
            'value' => $amount,
        ]);
    }
}
