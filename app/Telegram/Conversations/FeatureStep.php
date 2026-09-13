<?php

declare(strict_types=1);

namespace App\Telegram\Conversations;

use App\Contracts\FeatureManager;
use Closure;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

final class FeatureStep
{
    public static function guarded(string $feature, array $action, bool $default = false): Closure
    {
        return static function (TelegramUpdate $update, mixed $input, array $data) use ($feature, $action, $default): mixed {
            if (! app(FeatureManager::class)->enabled($feature, $default)) {
                return ['done' => false, 'data' => $data];
            }

            return app()->call($action, [
                'update' => $update,
                'input' => $input,
                'data' => $data,
            ]);
        };
    }
}
