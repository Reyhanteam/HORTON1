<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Telegram;

use App\Models\BotSetting;
use App\Models\RequiredTelegramChannel;
use App\Services\Telegram\DatabaseChannelMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;
use Tests\TestCase;

final class DatabaseChannelMembershipServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_feature_allows_without_api_call(): void
    {
        $update = BOT::fake()->message('/start');
        $service = app(DatabaseChannelMembershipService::class);

        $result = $service->check($update);

        self::assertTrue($result->allowed());
        self::assertFalse($result->enabled);
        BOT::getFacadeRoot()->assertNoApiCall('getChatMember');
    }

    public function test_member_is_allowed_for_all_required_channels(): void
    {
        $this->enableFeature();
        RequiredTelegramChannel::query()->create([
            'chat_id' => '-100111',
            'title' => 'Channel One',
            'username' => 'channel_one',
        ]);
        RequiredTelegramChannel::query()->create([
            'chat_id' => '-100222',
            'title' => 'Channel Two',
            'username' => 'channel_two',
        ]);

        $fake = BOT::fake()->respond('getChatMember', ['result' => ['status' => 'member']]);
        $update = $fake->message('/start');

        $result = app(DatabaseChannelMembershipService::class)->check($update);

        self::assertTrue($result->allowed());
        self::assertCount(0, $result->missingChannels);
        $fake->assertApiCalled('getChatMember');
    }

    public function test_non_member_is_blocked_and_missing_channels_are_returned(): void
    {
        $this->enableFeature();
        $channel = RequiredTelegramChannel::query()->create([
            'chat_id' => '-100111',
            'title' => 'Channel One',
            'username' => 'channel_one',
        ]);

        BOT::fake()->respond('getChatMember', ['result' => ['status' => 'left']]);
        $result = app(DatabaseChannelMembershipService::class)->check(BOT::getFacadeRoot()->message('/start'));

        self::assertFalse($result->allowed());
        self::assertFalse($result->member);
        self::assertSame([$channel->id], array_map(static fn ($item) => $item->id, $result->missingChannels));
    }

    public function test_api_failure_fails_closed(): void
    {
        $this->enableFeature();
        RequiredTelegramChannel::query()->create([
            'chat_id' => '-100111',
            'title' => 'Channel One',
            'username' => 'channel_one',
        ]);

        $fake = BOT::fake();
        $fake->respond('getChatMember', static function (): never {
            throw new \RuntimeException('Telegram API unavailable');
        });

        $result = app(DatabaseChannelMembershipService::class)->check($fake->message('/start'));

        self::assertFalse($result->allowed());
        self::assertTrue($result->unavailable);
    }

    private function enableFeature(): void
    {
        BotSetting::query()->create([
            'key' => 'features.channel_membership',
            'value' => 'true',
            'type' => 'boolean',
            'is_public' => false,
        ]);
    }
}
