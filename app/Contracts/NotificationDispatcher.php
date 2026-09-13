<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;

interface NotificationDispatcher
{
    public function dispatch(User $user, NotificationType|string $type, array $data = [], ?string $deduplicationKey = null): ?Notification;
}
