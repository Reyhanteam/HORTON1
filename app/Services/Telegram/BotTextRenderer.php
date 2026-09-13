<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Contracts\BotMessageStore;
use App\Models\User;

final class BotTextRenderer
{
    public function __construct(private readonly BotMessageStore $messages) {}

    public function render(string $key, User $user, array $data = [], ?string $type = 'message'): ?string
    {
        $template = $this->messages->get($key, $user->locale ?: 'fa', null, $type);

        if ($template === null) {
            return null;
        }

        $values = [
            '{name}' => (string) ($user->name ?: $user->username ?: ''),
            '{username}' => (string) ($user->username ?: ''),
            '{phone}' => (string) ($user->phone ?: ''),
        ];

        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value instanceof \Stringable) {
                $values['{'.trim((string) $key, '{}').'}'] = (string) $value;
            }
        }

        return strtr($template, $values);
    }
}
