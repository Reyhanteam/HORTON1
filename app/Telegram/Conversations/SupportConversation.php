<?php

declare(strict_types=1);

namespace App\Telegram\Conversations;

use App\Telegram\Controllers\SupportController;

final class SupportConversation
{
    public static function name(): string
    {
        return 'support-ticket';
    }

    public static function steps(): array
    {
        return [
            [SupportController::class, 'department'],
            [SupportController::class, 'sensitivity'],
            [SupportController::class, 'message'],
        ];
    }
}
