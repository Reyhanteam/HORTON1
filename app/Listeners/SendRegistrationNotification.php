<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Contracts\NotificationDispatcher;
use App\Enums\NotificationType;
use App\Events\UserRegistered;

final class SendRegistrationNotification
{
    public function handle(UserRegistered $event): void
    {
        app(NotificationDispatcher::class)->dispatch(
            $event->user,
            NotificationType::REGISTRATION_SUCCESS,
            [],
            'user:'.$event->user->id.':registered',
        );
    }
}
