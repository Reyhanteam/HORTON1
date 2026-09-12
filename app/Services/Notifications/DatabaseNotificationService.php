<?php

namespace App\Services\Notifications;

use App\Contracts\NotificationService;
use App\DTOs\NotificationData;
use App\Exceptions\DomainRuleViolation;
use App\Models\Notification;

final class DatabaseNotificationService implements NotificationService
{
    public function create(NotificationData $data): Notification
    {
        if ($data->title === '') throw new DomainRuleViolation('Notification title is required.', 'notification.title');
        return Notification::query()->create(['user_id'=>$data->userId,'type'=>$data->type,'title'=>$data->title,'body'=>$data->body,'data'=>$data->data]);
    }

    public function markRead(Notification $notification): Notification
    {
        if ($notification->read_at === null) $notification->forceFill(['read_at'=>now()])->save();
        return $notification->refresh();
    }
}
