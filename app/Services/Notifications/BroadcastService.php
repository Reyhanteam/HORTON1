<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Jobs\QueueBroadcastJob;
use App\Models\Broadcast;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class BroadcastService
{
    public function queue(Broadcast $broadcast): Broadcast
    {
        DB::transaction(function () use ($broadcast): void {
            $locked = Broadcast::query()->whereKey($broadcast->id)->lockForUpdate()->firstOrFail();
            if (in_array($locked->status, ['queued', 'sending', 'completed', 'cancelled'], true)) return;

            $locked->forceFill([
                'status' => 'queued',
                'started_at' => null,
                'completed_at' => null,
            ])->save();
        });

        QueueBroadcastJob::dispatch($broadcast->id);
        return $broadcast->refresh();
    }

    public function cancel(Broadcast $broadcast): Broadcast
    {
        return DB::transaction(function () use ($broadcast): Broadcast {
            $locked = Broadcast::query()->whereKey($broadcast->id)->lockForUpdate()->firstOrFail();
            if (in_array($locked->status, ['completed', 'cancelled'], true)) return $locked;
            $locked->forceFill(['status' => 'cancelled'])->save();
            return $locked->refresh();
        });
    }

    public function recipients(Broadcast $broadcast): Builder
    {
        $targeting = is_array($broadcast->targeting) ? $broadcast->targeting : [];
        $query = User::query()
            ->where('status', 'active')
            ->whereHas('telegramAccount', fn (Builder $q) => $q->where('is_active', true));

        if (array_key_exists('user_status', $targeting)) {
            $statuses = array_values(array_filter((array) $targeting['user_status'], 'is_string'));
            if ($statuses !== []) $query->whereIn('status', $statuses);
        }

        if (array_key_exists('locale', $targeting)) {
            $locales = array_values(array_filter((array) $targeting['locale'], 'is_string'));
            if ($locales !== []) $query->whereIn('locale', $locales);
        }

        if (array_key_exists('has_active_service', $targeting)) {
            $hasActiveService = filter_var($targeting['has_active_service'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($hasActiveService === true) {
                $query->whereHas('services', fn (Builder $q) => $q->where('status', 'active'));
            } elseif ($hasActiveService === false) {
                $query->whereDoesntHave('services', fn (Builder $q) => $q->where('status', 'active'));
            }
        }

        if (array_key_exists('has_trial_service', $targeting)) {
            $hasTrial = filter_var($targeting['has_trial_service'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($hasTrial === true) {
                $query->whereHas('services', fn (Builder $q) => $q->where('is_trial', true));
            } elseif ($hasTrial === false) {
                $query->whereDoesntHave('services', fn (Builder $q) => $q->where('is_trial', true));
            }
        }

        return $query->orderBy('id');
    }
}
