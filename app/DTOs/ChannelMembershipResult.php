<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\RequiredTelegramChannel;

final readonly class ChannelMembershipResult
{
    /** @param array<int, RequiredTelegramChannel> $missingChannels */
    public function __construct(
        public bool $enabled,
        public bool $checked,
        public bool $member,
        public bool $unavailable,
        public array $missingChannels = [],
    ) {}

    public function allowed(): bool
    {
        return !$this->enabled || ($this->checked && $this->member && !$this->unavailable);
    }
}
