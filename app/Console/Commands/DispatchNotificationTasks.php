<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\NotificationDispatcher;
use App\Enums\NotificationType;
use App\Enums\ServiceStatus;
use App\Models\Broadcast;
use App\Models\Service;
use App\Services\Notifications\BroadcastService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class DispatchNotificationTasks extends Command
{
    protected $signature = 'horton:notifications:dispatch';
    protected $description = 'Dispatch service expiry notifications and scheduled broadcasts.';

    public function handle(NotificationDispatcher $notifications, BroadcastService $broadcasts): int
    {
        $settings = app(\App\Contracts\SettingsStore::class);
        if (! (bool) $settings->get('notifications.enabled', true)) {
            $this->info('Notifications are disabled.');
            return self::SUCCESS;
        }

        $expired = $this->expireServices($notifications);
        $threeDays = $this->dispatchExpiring($notifications, 72, NotificationType::SERVICE_EXPIRING_3_DAYS, $settings->get('notifications.service_expiry.3_days.enabled', true));
        $twentyFourHours = $this->dispatchExpiring($notifications, 24, NotificationType::SERVICE_EXPIRING_24_HOURS, $settings->get('notifications.service_expiry.24_hours.enabled', true));
        $scheduled = $this->queueScheduledBroadcasts($broadcasts);

        $this->info("Expired: {$expired}; 3-day reminders: {$threeDays}; 24-hour reminders: {$twentyFourHours}; broadcasts queued: {$scheduled}.");
        return self::SUCCESS;
    }

    private function expireServices(NotificationDispatcher $notifications): int
    {
        $count = 0;
        Service::query()
            ->where('status', ServiceStatus::ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with('user')
            ->chunkById(100, function ($services) use ($notifications, &$count): void {
                foreach ($services as $service) {
                    DB::transaction(function () use ($service, $notifications, &$count): void {
                        $locked = Service::query()->with('user')->whereKey($service->id)->lockForUpdate()->first();
                        if (! $locked || $locked->status !== ServiceStatus::ACTIVE || ! $locked->expires_at?->isPast()) return;
                        $locked->forceFill(['status' => ServiceStatus::EXPIRED])->save();
                        $notifications->dispatch($locked->user, NotificationType::SERVICE_EXPIRED, [
                            'service_id' => $locked->id,
                            'service_uuid' => $locked->uuid,
                            'expires_at' => $locked->expires_at?->toDateTimeString() ?: '',
                        ], 'service:'.$locked->id.':expired');
                        $count++;
                    });
                }
            });
        return $count;
    }

    private function dispatchExpiring(NotificationDispatcher $notifications, int $hours, NotificationType $type, bool $enabled): int
    {
        if (! $enabled) return 0;

        $upper = now()->addHours($hours);
        $lower = $hours === 24 ? now() : now()->addHours(24);
        $count = 0;

        Service::query()
            ->where('status', ServiceStatus::ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', $lower)
            ->where('expires_at', '<=', $upper)
            ->with('user')
            ->chunkById(100, function ($services) use ($notifications, $type, &$count): void {
                foreach ($services as $service) {
                    $notifications->dispatch($service->user, $type, [
                        'service_id' => $service->id,
                        'service_uuid' => $service->uuid,
                        'expires_at' => $service->expires_at?->toDateTimeString() ?: '',
                    ], 'service:'.$service->id.':'.$type->value);
                    $count++;
                }
            });

        return $count;
    }

    private function queueScheduledBroadcasts(BroadcastService $broadcasts): int
    {
        $count = 0;
        Broadcast::query()
            ->whereIn('status', ['draft', 'scheduled'])
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->chunkById(50, function ($items) use ($broadcasts, &$count): void {
                foreach ($items as $broadcast) {
                    $broadcasts->queue($broadcast);
                    $count++;
                }
            });
        return $count;
    }
}
