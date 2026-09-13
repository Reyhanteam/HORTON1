<?php

declare(strict_types=1);

namespace App\Telegram\Conversations;

use App\Contracts\FeatureManager;
use Closure;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

final class FeatureStep
{
    /**
     * Build a conversation step that skips itself when its feature is disabled.
     *
     * The Telegram router still owns conversation state and step progression.
     * This helper only decides whether the application callback should run.
     *
     * @param array{0:class-string,1:string} $action
     */
    public static function guarded(string $feature, array $action, bool $default = false): Closure
    {
        return static function (
            TelegramUpdate $update,
            mixed $input,
            array $data,
        ) use ($feature, $action, $default): mixed {
            $features = app(FeatureManager::class);

            if (! $features->enabled($feature, $default)) {
                return [
                    'done' => false,
                    'data' => $data,
                ];
            }

            return app()->call($action, [
                'update' => $update,
                'input' => $input,
                'data' => $data,
            ]);
        };
    }
}
