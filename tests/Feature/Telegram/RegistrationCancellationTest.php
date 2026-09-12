<?php

declare(strict_types=1);

namespace Tests\Feature\Telegram;

use App\Enums\Feature;
use App\Enums\UserStatus;
use App\Models\BotSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use ReyhanTeam\TelegramBotRouter\Facades\Telegram;
use Tests\TestCase;

final class RegistrationCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        BotSetting::query()->updateOrCreate(
            ['key' => 'features.' . Feature::PhoneVerification->value],
            ['value' => 'true', 'type' => 'boolean', 'is_public' => false],
        );
        BotSetting::query()->updateOrCreate(
            ['key' => 'messages.registration.cancelled'],
            ['value' => json_encode('Registration cancelled', JSON_THROW_ON_ERROR), 'type' => 'string', 'is_public' => true],
        );
    }

    public function test_cancel_command_ends_the_package_conversation(): void
    {
        Telegram::fake()->respond('sendMessage', ['message_id' => 1]);

        $this->postJson('/telegram/webhook', self::startUpdate());
        $this->postJson('/telegram/webhook', self::callbackUpdate('registration:accept'));
        $this->postJson('/telegram/webhook', self::cancelUpdate());
        $this->postJson('/telegram/webhook', self::contactUpdate());

        self::assertSame(UserStatus::Pending, User::query()->sole()->status);
        self::assertNull(User::query()->sole()->phone);
    }

    public function test_wrong_acceptance_input_does_not_advance_the_conversation(): void
    {
        Telegram::fake()->respond('sendMessage', ['message_id' => 1]);

        $this->postJson('/telegram/webhook', self::startUpdate());
        $this->postJson('/telegram/webhook', self::textUpdate('not-an-acceptance'));
        $this->postJson('/telegram/webhook', self::callbackUpdate('registration:accept'));
        $this->postJson('/telegram/webhook', self::contactUpdate());

        $user = User::query()->sole();
        self::assertSame(UserStatus::Active, $user->status);
        self::assertSame('+491234567890', $user->phone);
    }

    private static function startUpdate(): array
    {
        return [
            'update_id' => 61001,
            'message' => [
                'message_id' => 71001,
                'from' => ['id' => 1001, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => 1001, 'type' => 'private'],
                'date' => time(),
                'text' => '/start',
            ],
        ];
    }

    private static function callbackUpdate(string $data): array
    {
        return [
            'update_id' => 61002,
            'callback_query' => [
                'id' => 'cancel-test',
                'from' => ['id' => 1001, 'is_bot' => false, 'first_name' => 'Test'],
                'message' => [
                    'message_id' => 71002,
                    'from' => ['id' => 999000, 'is_bot' => true, 'first_name' => 'Horton'],
                    'chat' => ['id' => 1001, 'type' => 'private'],
                    'date' => time(),
                    'text' => 'Rules',
                ],
                'chat_instance' => 'test',
                'data' => $data,
            ],
        ];
    }

    private static function cancelUpdate(): array
    {
        return [
            'update_id' => 61003,
            'message' => [
                'message_id' => 71003,
                'from' => ['id' => 1001, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => 1001, 'type' => 'private'],
                'date' => time(),
                'text' => '/cancel',
            ],
        ];
    }

    private static function textUpdate(string $text): array
    {
        $update = self::startUpdate();
        $update['update_id'] = 61004;
        $update['message']['message_id'] = 71004;
        $update['message']['text'] = $text;
        return $update;
    }

    private static function contactUpdate(): array
    {
        return [
            'update_id' => 61005,
            'message' => [
                'message_id' => 71005,
                'from' => ['id' => 1001, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => 1001, 'type' => 'private'],
                'date' => time(),
                'contact' => [
                    'phone_number' => '+491234567890',
                    'first_name' => 'Test',
                    'user_id' => 1001,
                ],
            ],
        ];
    }
}
