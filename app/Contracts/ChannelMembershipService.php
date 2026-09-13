<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTOs\ChannelMembershipResult;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

interface ChannelMembershipService
{
    public function check(TelegramUpdate $update): ChannelMembershipResult;
}
