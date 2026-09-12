<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TelegramAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelegramAccount>
 */
final class TelegramAccountFactory extends Factory
{
    protected $model = TelegramAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'telegram_user_id' => fake()->unique()->numberBetween(100000000, 999999999),
            'username' => fake()->userName(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'language_code' => 'en',
            'is_bot' => false,
            'is_active' => true,
            'last_seen_at' => now(),
        ];
    }
}
