<?php

namespace App\Actions\Notifications;

use App\Contracts\NotificationService;
use App\DTOs\NotificationData;
use App\Models\Notification;

final class CreateNotificationAction
{
    public function __construct(private readonly NotificationService $notifications) {}
    public function execute(NotificationData $data): Notification { return $this->notifications->create($data); }
}
