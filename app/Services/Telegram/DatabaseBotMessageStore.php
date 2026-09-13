<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Contracts\BotMessageStore;
use App\Models\BotMessage;

final class DatabaseBotMessageStore implements BotMessageStore
{
    public function get(string $key, ?string $locale = null, ?string $default = null): ?string
    {
        $locale ??= (string) config('app.locale', 'fa');

        $message = BotMessage::query()
            ->active()
            ->where('key', $key)
            ->where('locale', $locale)
            ->value('text');

        if ($message !== null) {
            return (string) $message;
        }

        if ($locale !== 'fa') {
            $message = BotMessage::query()
                ->active()
                ->where('key', $key)
                ->where('locale', 'fa')
                ->value('text');

            if ($message !== null) {
                return (string) $message;
            }
        }

        return $default;
    }
}
