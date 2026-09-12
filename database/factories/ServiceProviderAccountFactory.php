<?php

namespace Database\Factories;

use App\Models\ServiceProvider;
use App\Models\ServiceProviderAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceProviderAccountFactory extends Factory
{
    protected $model = ServiceProviderAccount::class;

    public function definition(): array
    {
        return [
            'service_provider_id' => ServiceProvider::factory(),
            'name' => fake()->company().' Account',
            'identifier' => fake()->unique()->userName(),
            'credentials' => ['api_key' => fake()->sha256()],
            'status' => 'active',
            'priority' => 0,
            'metadata' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
