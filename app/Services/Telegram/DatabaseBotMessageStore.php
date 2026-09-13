<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Contracts\BotMessageStore;
use App\Models\BotMessage;

final class DatabaseBotMessageStore implements BotMessageStore
{
    public function get(
        string $key,
        ?string $locale = null,
        ?string $default = null,
        ?string $type = null,
    ): ?string {
        $locale ??= (string) config('app.locale', 'fa');

        $message = $this->query($key, $locale, $type)->value('text');

        if ($message !== null) {
            return (string) $message;
        }

        if ($locale !== 'fa') {
            $message = $this->query($key, 'fa', $type)->value('text');

            if ($message !== null) {
                return (string) $message;
            }
        }

        return $default;
    }

    private function query(string $key, string $locale, ?string $type)
    {
        return BotMessage::query()
            ->active()
            ->where('key', $key)
            ->where('locale', $locale)
            ->when($type !== null, fn ($query) => $query->where('type', $type));
    }
}
