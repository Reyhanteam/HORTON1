<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Telegram;

use App\Contracts\SettingsStore;
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
        $fake = BOT::fake();
        $update = $fake->message('/start');

        $result = app(DatabaseChannelMembershipService::class)->check($update);

        self::assertTrue($result->allowed());
        self::assertFalse($result->enabled);
        $fake->assertNoApiCall('getChatMember');
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
        $result = app(DatabaseChannelMembershipService::class)->check($fake->message('/start'));

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

        $fake = BOT::fake()->respond('getChatMember', ['result' => ['status' => 'left']]);
        $result = app(DatabaseChannelMembershipService::class)->check($fake->message('/start'));

        self::assertFalse($result->allowed());
        self::assertFalse($result->member);
        self::assertSame([$channel->id], array_map(static fn ($item) => $item->id, $result->missingChannels));
    }

    public function test_invalid_api_response_fails_closed(): void
    {
        $this->enableFeature();
        RequiredTelegramChannel::query()->create([
            'chat_id' => '-100111',
            'title' => 'Channel One',
            'username' => 'channel_one',
        ]);

        $fake = BOT::fake()->respond('getChatMember', ['ok' => false]);
        $result = app(DatabaseChannelMembershipService::class)->check($fake->message('/start'));

        self::assertFalse($result->allowed());
        self::assertTrue($result->unavailable);
    }

    private function enableFeature(): void
    {
        app(SettingsStore::class)->set('features.channel_membership', true);
    }
}
