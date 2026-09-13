<?php

declare(strict_types=1);

namespace App\Telegram\Middleware;

use App\Contracts\ChannelMembershipService;
use App\Telegram\Controllers\ChannelMembershipController;
use Closure;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

final class EnsureChannelMembership
{
    public function __construct(
        private readonly ChannelMembershipService $membership,
        private readonly ChannelMembershipController $controller,
    ) {}

    public function handle(TelegramUpdate $update, Closure $next): mixed
    {
        $result = $this->membership->check($update);

        if (!$result->allowed()) {
            return $this->controller->sendRestriction($update, $result);
        }

        return $next($update);
    }
}
