<?php

declare(strict_types=1);

namespace App\Telegram\Controllers;

use App\Contracts\ChannelMembershipService;
use App\Contracts\SettingsStore;
use App\DTOs\ChannelMembershipResult;
use App\Models\RequiredTelegramChannel;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;
use ReyhanTeam\TelegramBotRouter\Keyboard\Keyboard;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

final class ChannelMembershipController
{
    public const RECHECK = 'membership:check';

    public function __construct(
        private readonly ChannelMembershipService $membership,
        private readonly SettingsStore $settings,
    ) {}

    public function recheck(TelegramUpdate $update): mixed
    {
        $result = $this->membership->check($update);
        if ($result->allowed()) {
            return BOT::sendMessage($update->chatId(), $this->message('membership.verified'));
        }

        return $this->sendRestriction($update, $result);
    }

    public function sendRestriction(TelegramUpdate $update, ChannelMembershipResult $result): mixed
    {
        if ($result->unavailable) {
            return BOT::sendMessage(
                $update->chatId(),
                $this->message('membership.unavailable'),
                replyMarkup: Keyboard::inline()
                    ->callbackButton($this->message('membership.recheck'), self::RECHECK)
                    ->toArray(),
            );
        }

        $rows = [];
        foreach ($result->missingChannels as $channel) {
            $url = $this->channelUrl($channel);
            if ($url !== null) {
                $rows[] = [[
                    'text' => '📢 ' . $channel->title,
                    'url' => $url,
                ]];
            }
        }

        $rows[] = [[
            'text' => $this->message('membership.recheck'),
            'callback_data' => self::RECHECK,
        ]];

        return BOT::sendMessage(
            $update->chatId(),
            $this->message('membership.restricted'),
            replyMarkup: Keyboard::inline()->rows($rows)->toArray(),
        );
    }

    private function message(string $key): string
    {
        return (string) $this->settings->get('messages.' . $key, $key);
    }

    private function channelUrl(RequiredTelegramChannel $channel): ?string
    {
        if (is_string($channel->invite_url) && $channel->invite_url !== '') {
            return $channel->invite_url;
        }

        if (is_string($channel->username) && $channel->username !== '') {
            return 'https://t.me/' . ltrim($channel->username, '@');
        }

        return null;
    }
}
