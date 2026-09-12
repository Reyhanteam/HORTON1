<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(), 'order_id' => Order::factory(), 'user_id' => User::factory(),
            'method' => 'online', 'gateway' => 'fake', 'amount' => 100000, 'currency' => 'IRR',
            'status' => PaymentStatus::PENDING, 'transaction_id' => null, 'reference_id' => null,
            'idempotency_key' => null, 'metadata' => [],
        ];
    }
    public function success(): static { return $this->state(['status' => PaymentStatus::SUCCESS, 'paid_at' => now(), 'verified_at' => now()]); }
    public function failed(): static { return $this->state(['status' => PaymentStatus::FAILED]); }
}
