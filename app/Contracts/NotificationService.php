<?php

namespace App\Contracts;

use App\DTOs\NotificationData;
use App\Models\Notification;

interface NotificationService
{
    public function create(NotificationData $data): Notification;
    public function markRead(Notification $notification): Notification;
}
