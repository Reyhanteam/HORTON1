<?php

namespace Database\Factories;

use App\Enums\ServiceStatus;
use App\Models\Plan;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'order_id' => null,
            'order_item_id' => null,
            'plan_id' => Plan::factory(),
            'service_provider_id' => ServiceProvider::factory(),
            'provider_account_id' => null,
            'status' => ServiceStatus::PENDING,
            'external_id' => null,
            'external_reference' => null,
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'capacity' => 1,
            'used_capacity' => 0,
            'is_trial' => false,
            'metadata' => [],
        ];
    }

    public function provisioned(): static
    {
        return $this->state(fn () => [
            'status' => ServiceStatus::ACTIVE,
            'external_id' => 'fake-'.Str::lower(Str::random(20)),
            'external_reference' => 'fake-ref-'.Str::lower(Str::random(16)),
        ]);
    }
}
