<?php

declare(strict_types=1);

namespace Tests\Feature\Telegram;

use App\Enums\Feature;
use App\Enums\UserStatus;
use App\Models\BotSetting;
use App\Models\TelegramAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use ReyhanTeam\TelegramBotRouter\Facades\Telegram;
use Tests\TestCase;

final class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seedMessages();
    }

    public function test_start_creates_pending_user_and_telegram_account_and_starts_conversation(): void
    {
        $fake = Telegram::fake();
        $fake->respond('sendMessage', ['message_id' => 10]);

        $response = $this->postJson('/telegram/webhook', self::startUpdate(1001));

        $response->assertOk();
        $user = User::query()->sole();

        self::assertSame(UserStatus::Pending, $user->status);
        self::assertNull($user->phone);
        self::assertDatabaseHas('telegram_accounts', [
            'user_id' => $user->id,
            'telegram_user_id' => 1001,
        ]);
        $fake->assertMessageSent('Rules for Test');
    }

    public function test_accepting_rules_requests_phone_when_feature_is_enabled(): void
    {
        $this->startRegistration();
        $fake = Telegram::fake();
        $fake->respond('sendMessage', ['message_id' => 11]);

        $response = $this->postJson('/telegram/webhook', self::callbackUpdate(1001, 'registration:accept'));

        $response->assertOk();
        $fake->assertMessageSent('Phone prompt');
    }

    public function test_valid_contact_completes_registration_and_marks_phone_verified(): void
    {
        $this->startRegistration();
        $this->postJson('/telegram/webhook', self::callbackUpdate(1001, 'registration:accept'));

        $fake = Telegram::fake();
        $fake->respond('sendMessage', ['message_id' => 12]);

        $response = $this->postJson('/telegram/webhook', self::contactUpdate(1001, 1001, '+49 123 456 7890'));

        $response->assertOk();
        $user = User::query()->sole();

        self::assertSame(UserStatus::Active, $user->status);
        self::assertSame('+491234567890', $user->phone);
        self::assertNotNull($user->phone_verified_at);
        $fake->assertMessageSent('Registration complete');
    }

    public function test_contact_from_another_telegram_user_is_rejected(): void
    {
        $this->startRegistration();
        $this->postJson('/telegram/webhook', self::callbackUpdate(1001, 'registration:accept'));

        $this->postJson('/telegram/webhook', self::contactUpdate(1001, 9999, '+491234567890'));

        $user = User::query()->sole();
        self::assertSame(UserStatus::Pending, $user->status);
        self::assertNull($user->phone);
    }

    public function test_invalid_contact_is_rejected_and_conversation_remains_active(): void
    {
        $this->startRegistration();
        $this->postJson(
            '/telegram/webhook',
            self::callbackUpdate(1001, 'registration:accept')
        );

        $this->postJson(
            '/telegram/webhook',
            self::contactUpdate(1001, 1001, '+123')
        );

        $user = User::query()->sole();
        self::assertSame(UserStatus::Pending, $user->status);
        self::assertNull($user->phone);
    }

    public function test_duplicate_phone_is_rejected(): void
    {
        $existing = User::factory()->create([
            'phone' => '+491234567890',
            'phone_verified_at' => now(),
            'status' => UserStatus::Active,
        ]);
        TelegramAccount::factory()->create(['user_id' => $existing->id, 'telegram_user_id' => 2002]);

        $this->startRegistration(1001);
        $this->postJson('/telegram/webhook', self::callbackUpdate(1001, 'registration:accept'));
        $this->postJson('/telegram/webhook', self::contactUpdate(1001, 1001, '+491234567890'));

        $newUser = User::query()->where('id', '!=', $existing->id)->sole();
        self::assertSame(UserStatus::Pending, $newUser->status);
        self::assertNull($newUser->phone);
    }

    public function test_phone_verification_feature_can_be_disabled_from_database(): void
    {
        BotSetting::query()->updateOrCreate(
            ['key' => 'features.'.Feature::PhoneVerification->value],
            ['value' => 'false', 'type' => 'boolean', 'is_public' => false],
        );

        $this->startRegistration();
        $fake = Telegram::fake();
        $fake->respond('sendMessage', ['message_id' => 13]);

        $response = $this->postJson('/telegram/webhook', self::callbackUpdate(1001, 'registration:accept'));

        $response->assertOk();
        self::assertSame(UserStatus::Active, User::query()->sole()->status);
        self::assertNull(User::query()->sole()->phone);
        $fake->assertMessageSent('Registration complete');
    }

    public function test_declining_rules_finishes_registration_without_activation(): void
    {
        $this->startRegistration();
        $this->postJson('/telegram/webhook', self::callbackUpdate(1001, 'registration:decline'));

        $user = User::query()->sole();
        self::assertSame(UserStatus::Pending, $user->status);
        self::assertNull($user->phone);
    }

    private function startRegistration(int $telegramUserId = 1001): void
    {
        Telegram::fake()->respond('sendMessage', ['message_id' => 10]);
        $this->postJson('/telegram/webhook', self::startUpdate($telegramUserId));
    }

    private function seedMessages(): void
    {
        $messages = [
            'registration.rules' => 'Rules for {name}',
            'registration.accept_button' => 'Accept',
            'registration.decline_button' => 'Decline',
            'registration.invalid_acceptance' => 'Invalid acceptance',
            'registration.phone_prompt' => 'Phone prompt',
            'registration.share_phone_button' => 'Share phone',
            'registration.phone_required' => 'Phone required',
            'registration.phone_invalid' => 'Phone invalid',
            'registration.phone_owner_mismatch' => 'Phone owner mismatch',
            'registration.phone_taken' => 'Phone already used',
            'registration.success' => 'Registration complete',
            'registration.cancelled' => 'Registration cancelled',
            'registration.already_active' => 'Already active',
            'registration.blocked' => 'Blocked',
            'registration.not_started' => 'Registration not started',
            'registration.telegram_user_required' => 'Telegram user required',
        ];

        foreach ($messages as $key => $value) {
            BotSetting::query()->updateOrCreate(
                ['key' => 'messages.'.$key],
                ['value' => json_encode($value, JSON_THROW_ON_ERROR), 'type' => 'string', 'is_public' => true],
            );
        }

        BotSetting::query()->updateOrCreate(
            ['key' => 'features.'.Feature::PhoneVerification->value],
            ['value' => 'true', 'type' => 'boolean', 'is_public' => false],
        );
    }

    private static function startUpdate(int $telegramUserId): array
    {
        return [
            'update_id' => random_int(10000, 99999),
            'message' => [
                'message_id' => random_int(10000, 99999),
                'from' => ['id' => $telegramUserId, 'is_bot' => false, 'first_name' => 'Test', 'username' => 'test_user'],
                'chat' => ['id' => $telegramUserId, 'type' => 'private'],
                'date' => time(),
                'text' => '/start',
            ],
        ];
    }

    private static function callbackUpdate(int $telegramUserId, string $data): array
    {
        return [
            'update_id' => random_int(10000, 99999),
            'callback_query' => [
                'id' => 'callback-'.random_int(10000, 99999),
                'from' => ['id' => $telegramUserId, 'is_bot' => false, 'first_name' => 'Test'],
                'message' => [
                    'message_id' => random_int(10000, 99999),
                    'from' => ['id' => 999000, 'is_bot' => true, 'first_name' => 'Horton'],
                    'chat' => ['id' => $telegramUserId, 'type' => 'private'],
                    'date' => time(),
                    'text' => 'Rules',
                ],
                'chat_instance' => 'test',
                'data' => $data,
            ],
        ];
    }

    private static function contactUpdate(int $telegramUserId, int $contactUserId, string $phone): array
    {
        return [
            'update_id' => random_int(10000, 99999),
            'message' => [
                'message_id' => random_int(10000, 99999),
                'from' => ['id' => $telegramUserId, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => $telegramUserId, 'type' => 'private'],
                'date' => time(),
                'contact' => [
                    'phone_number' => $phone,
                    'first_name' => 'Test',
                    'user_id' => $contactUserId,
                ],
            ],
        ];
    }
}
