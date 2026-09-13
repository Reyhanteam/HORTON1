<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Contracts\ChannelMembershipService as ChannelMembershipServiceContract;
use App\Contracts\FeatureManager;
use App\DTOs\ChannelMembershipResult;
use App\Enums\Feature;
use App\Models\RequiredTelegramChannel;
use Illuminate\Support\Facades\Log;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

final class DatabaseChannelMembershipService implements ChannelMembershipServiceContract
{
    private const MEMBER_STATUSES = ['creator', 'administrator', 'member', 'restricted'];

    public function __construct(private readonly FeatureManager $features) {}

    public function check(TelegramUpdate $update): ChannelMembershipResult
    {
        if (!$this->features->enabled(Feature::ChannelMembership->value, false)) {
            return new ChannelMembershipResult(false, false, true, false);
        }

        $channels = RequiredTelegramChannel::query()
            ->where('is_active', true)
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($channels->isEmpty()) {
            return new ChannelMembershipResult(true, true, true, false);
        }

        $userId = $update->userId();
        if ($userId === null) {
            return new ChannelMembershipResult(true, false, false, true, $channels->all());
        }

        $missing = [];
        foreach ($channels as $channel) {
            try {
                $response = BOT::getChatMember($channel->chat_id, (int) $userId);
                $status = $this->status($response);
                if ($status === null) {
                    Log::warning('Telegram channel membership response was invalid.', [
                        'channel_id' => $channel->chat_id,
                        'telegram_user_id' => $userId,
                    ]);
                    return new ChannelMembershipResult(true, false, false, true, $channels->all());
                }

                if (!in_array($status, self::MEMBER_STATUSES, true)) {
                    $missing[] = $channel;
                }
            } catch (\Throwable $exception) {
                Log::warning('Telegram channel membership check failed.', [
                    'channel_id' => $channel->chat_id,
                    'telegram_user_id' => $userId,
                    'exception' => $exception::class,
                ]);
                return new ChannelMembershipResult(true, false, false, true, $channels->all());
            }
        }

        return new ChannelMembershipResult(true, true, $missing === [], false, $missing);
    }

    private function status(mixed $response): ?string
    {
        if (is_array($response)) {
            $result = $response['result'] ?? $response;
            return is_array($result) && isset($result['status']) ? (string) $result['status'] : null;
        }

        if (is_object($response)) {
            $result = $response->result ?? $response;
            return is_object($result) && isset($result->status) ? (string) $result->status : null;
        }

        return null;
    }
}
