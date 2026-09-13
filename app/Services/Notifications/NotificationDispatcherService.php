<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Contracts\NotificationDispatcher;
use App\Contracts\SettingsStore;
use App\Enums\NotificationType;
use App\Jobs\SendTelegramNotificationJob;
use App\Models\Notification;
use App\Models\User;
use App\Services\Telegram\BotTextRenderer;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

final class NotificationDispatcherService implements NotificationDispatcher
{
    public function __construct(
        private readonly BotTextRenderer $renderer,
        private readonly SettingsStore $settings,
    ) {}

    public function dispatch(User $user, NotificationType|string $type, array $data = [], ?string $deduplicationKey = null): ?Notification
    {
        if (! (bool) $this->settings->get('notifications.enabled', true)) return null;

        $type = $type instanceof NotificationType ? $type : NotificationType::from($type);
        $title = $this->renderer->render('notifications.'.$type->value.'.title', $user, $data);
        $body = $this->renderer->render('notifications.'.$type->value.'.body', $user, $data);

        if ($title === null || $body === null) {
            Log::warning('Notification template is missing; notification skipped.', [
                'type' => $type->value,
                'user_id' => $user->id,
                'missing' => ['title' => $title === null, 'body' => $body === null],
            ]);
            return null;
        }

        try {
            $notification = Notification::query()->create([
                'user_id' => $user->id,
                'type' => $type->value,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'deduplication_key' => $deduplicationKey,
            ]);
        } catch (QueryException $e) {
            if ($deduplicationKey === null) throw $e;
            $notification = Notification::query()->where('deduplication_key', $deduplicationKey)->first();
            if (! $notification) throw $e;
        }

        $delivery = $notification->deliveries()->firstOrCreate(
            ['channel' => 'telegram'],
            ['status' => 'pending', 'attempts' => 0],
        );

        if ($delivery->sent_at === null) SendTelegramNotificationJob::dispatch($notification->id);

        return $notification->refresh();
    }
}
