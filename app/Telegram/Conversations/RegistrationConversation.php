<?php

declare(strict_types=1);

namespace App\Telegram\Conversations;

use App\Enums\Feature;
use App\Telegram\Controllers\RegistrationController;

final class RegistrationConversation
{
    public static function name(): string
    {
        return 'registration';
    }

    public static function steps(): array
    {
        return [
            [RegistrationController::class, 'acceptance'],
            FeatureStep::guarded(
                Feature::PhoneVerification->value,
                [RegistrationController::class, 'phone'],
                true,
            ),
        ];
    }
}
