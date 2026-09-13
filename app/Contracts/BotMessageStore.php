<?php

declare(strict_types=1);

namespace App\Contracts;

interface BotMessageStore
{
    public function get(string $key, ?string $locale = null, ?string $default = null): ?string;
}
