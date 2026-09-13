<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\NotificationDispatcher;
use App\Contracts\SettingsStore;
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

    public function handle(NotificationDispatcher $notifications, BroadcastService $broadcasts, SettingsStore $settings): int
    {
        $scheduled = $this->queueScheduledBroadcasts($broadcasts);

        if (! (bool) $settings->get('notifications.enabled', true)) {
            $this->info("Notifications are disabled; broadcasts queued: {$scheduled}.");
            return self::SUCCESS;
        }

        $threeDaysHours = max(1, (int) $settings->get('notifications.service_expiry.3_days.hours', 72));
        $twentyFourHours = max(1, (int) $settings->get('notifications.service_expiry.24_hours.hours', 24));

        $expired = $this->expireServices($notifications);
        $threeDays = $this->dispatchExpiring(
            $notifications,
            $threeDaysHours,
            $twentyFourHours,
            NotificationType::SERVICE_EXPIRING_3_DAYS,
            (bool) $settings->get('notifications.service_expiry.3_days.enabled', true),
        );
        $twentyFourHoursCount = $this->dispatchExpiring(
            $notifications,
            $twentyFourHours,
            0,
            NotificationType::SERVICE_EXPIRING_24_HOURS,
            (bool) $settings->get('notifications.service_expiry.24_hours.enabled', true),
        );

        $this->info("Expired: {$expired}; {$threeDaysHours}-hour reminders: {$threeDays}; {$twentyFourHours}-hour reminders: {$twentyFourHoursCount}; broadcasts queued: {$scheduled}.");
        return self::SUCCESS;
    }

    private function expireServices(NotificationDispatcher $notifications): int
    {
        $count = 0;
        Service::query()
            ->where('status', ServiceStatus::ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($services) use ($notifications, &$count): void {
                foreach ($services as $service) {
                    DB::transaction(function () use ($service, $notifications, &$count): void {
                        $locked = Service::query()->with('user')->whereKey($service->id)->lockForUpdate()->first();
                        if (! $locked || $locked->status !== ServiceStatus::ACTIVE || ! $locked->expires_at?->isPast()) return;
                        $locked->forceFill(['status' => ServiceStatus::EXPIRED])->save();
                        $notification = $notifications->dispatch($locked->user, NotificationType::SERVICE_EXPIRED, [
                            'service_id' => $locked->id,
                            'service_uuid' => $locked->uuid,
                            'expires_at' => $locked->expires_at?->toDateTimeString() ?: '',
                        ], 'service:'.$locked->id.':expired');
                        if ($notification !== null) $count++;
                    });
                }
            });
        return $count;
    }

    private function dispatchExpiring(NotificationDispatcher $notifications, int $hours, int $lowerHours, NotificationType $type, bool $enabled): int
    {
        if (! $enabled) return 0;

        $upper = now()->addHours($hours);
        $query = Service::query()
            ->where('status', ServiceStatus::ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now());

        if ($lowerHours > 0) $query->where('expires_at', '>', now()->addHours($lowerHours));
        $query->where('expires_at', '<=', $upper)->with('user');

        $count = 0;
        $query->chunkById(100, function ($services) use ($notifications, $type, &$count): void {
            foreach ($services as $service) {
                $notification = $notifications->dispatch($service->user, $type, [
                    'service_id' => $service->id,
                    'service_uuid' => $service->uuid,
                    'expires_at' => $service->expires_at?->toDateTimeString() ?: '',
                ], 'service:'.$service->id.':'.$type->value);
                if ($notification !== null) $count++;
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
