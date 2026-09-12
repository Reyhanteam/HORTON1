<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'status' => OrderStatus::PENDING->value,
            'subtotal' => 0,
            'discount_amount' => 0,
            'cashback_amount' => 0,
            'wallet_amount' => 0,
            'total_amount' => 0,
            'currency' => 'IRR',
            'metadata' => [],
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'status' => OrderStatus::PAID->value,
            'paid_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => OrderStatus::COMPLETED->value,
            'paid_at' => now(),
            'completed_at' => now(),
        ]);
    }
}
