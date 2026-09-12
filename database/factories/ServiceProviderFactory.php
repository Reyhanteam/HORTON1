<?php

namespace Database\Factories;

use App\Models\ServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServiceProviderFactory extends Factory
{
    protected $model = ServiceProvider::class;

    public function definition(): array
    {
        $name = fake()->company().' Provider';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'driver' => 'fake',
            'status' => 'active',
            'configuration' => [],
            'metadata' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
