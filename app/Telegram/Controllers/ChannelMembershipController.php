<?php

declare(strict_types=1);

namespace App\Telegram\Controllers;

use App\Contracts\BotMessageStore;
use App\Contracts\ChannelMembershipService;
use App\DTOs\ChannelMembershipResult;
use App\Models\BotChannel;
use App\Services\Telegram\BotMessageResponder;
use ReyhanTeam\TelegramBotRouter\Keyboard\Keyboard;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

final class ChannelMembershipController
{
    public const RECHECK = 'membership:check';

    public function __construct(
        private readonly ChannelMembershipService $membership,
        private readonly BotMessageStore $messages,
        private readonly BotMessageResponder $responder,
        private readonly RegistrationController $registration,
    ) {}

    public function recheck(TelegramUpdate $update): mixed
    {
        $result = $this->membership->check($update);

        if ($result->allowed()) {
            // Membership verification is a gate, not an onboarding step.
            // Continue directly with the registration conversation. The
            // existing membership message is edited into the rules message.
            return $this->registration->start($update);
        }

        return $this->sendRestriction($update, $result);
    }

    public function sendRestriction(TelegramUpdate $update, ChannelMembershipResult $result): mixed
    {
        if ($result->unavailable) {
            return $this->responder->respond(
                $update,
                $this->message('membership.unavailable'),
                Keyboard::inline()
                    ->callbackButton($this->button('membership.recheck'), self::RECHECK)
                    ->toArray(),
            );
        }

        $keyboard = Keyboard::inline();

        foreach ($result->missingChannels as $channel) {
            $url = $this->channelUrl($channel);

            if ($url !== null) {
                $keyboard->url('📢 ' . $channel->title, $url)->row();
            }
        }

        $keyboard->callbackButton($this->button('membership.recheck'), self::RECHECK);

        return $this->responder->respond(
            $update,
            $this->message('membership.restricted'),
            $keyboard->toArray(),
        );
    }

    private function message(string $key): string
    {
        return $this->messages->get($key, default: $key, type: 'message') ?? $key;
    }

    private function button(string $key): string
    {
        return $this->messages->get($key, default: $key, type: 'button') ?? $key;
    }

    private function channelUrl(BotChannel $channel): ?string
    {
        if (is_string($channel->username) && $channel->username !== '') {
            return 'https://t.me/' . ltrim($channel->username, '@');
        }

        return null;
    }
}
