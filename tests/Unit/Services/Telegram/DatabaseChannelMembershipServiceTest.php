<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Telegram;

use App\Contracts\SettingsStore;
use App\Models\BotChannel;
use App\Services\Telegram\DatabaseChannelMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;
use Tests\TestCase;

final class DatabaseChannelMembershipServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_feature_allows_without_api_call(): void
    {
        $fake = BOT::fake();
        $result = app(DatabaseChannelMembershipService::class)->check($fake->message('/start'));
        self::assertTrue($result->allowed());
        self::assertFalse($result->enabled);
        $fake->assertNoApiCall('getChatMember');
    }

    public function test_member_is_allowed_for_all_required_channels(): void
    {
        $this->enableFeature();
        $this->channel(-100111, 'Channel One', 'channel_one');
        $this->channel(-100222, 'Channel Two', 'channel_two');
        $fake = BOT::fake()->respond('getChatMember', ['result' => ['status' => 'member']]);
        $result = app(DatabaseChannelMembershipService::class)->check($fake->message('/start'));
        self::assertTrue($result->allowed());
        self::assertCount(0, $result->missingChannels);
        $fake->assertApiCalled('getChatMember');
    }

    public function test_non_member_is_blocked_and_missing_channels_are_returned(): void
    {
        $this->enableFeature();
        $channel = $this->channel(-100111, 'Channel One', 'channel_one');
        $fake = BOT::fake()->respond('getChatMember', ['result' => ['status' => 'left']]);
        $result = app(DatabaseChannelMembershipService::class)->check($fake->message('/start'));
        self::assertFalse($result->allowed());
        self::assertFalse($result->member);
        self::assertSame([$channel->id], array_map(static fn ($item) => $item->id, $result->missingChannels));
    }

    public function test_invalid_api_response_fails_closed(): void
    {
        $this->enableFeature();
        $this->channel(-100111, 'Channel One', 'channel_one');
        $fake = BOT::fake()->respond('getChatMember', ['ok' => false]);
        $result = app(DatabaseChannelMembershipService::class)->check($fake->message('/start'));
        self::assertFalse($result->allowed());
        self::assertTrue($result->unavailable);
    }

    private function enableFeature(): void
    {
        app(SettingsStore::class)->set('features.channel_membership', true);
    }

    private function channel(int $chatId, string $title, string $username): BotChannel
    {
        return BotChannel::query()->create([
            'telegram_chat_id' => $chatId,
            'title' => $title,
            'username' => $username,
            'type' => 'channel',
            'is_required' => true,
            'is_active' => true,
        ]);
    }
}
