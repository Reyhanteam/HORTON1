<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\ConfiguresHortonQueue;
use App\Models\Broadcast;
use App\Services\Notifications\BroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

final class QueueBroadcastJob implements ShouldQueue, ShouldBeUnique
{
    use ConfiguresHortonQueue;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $uniqueFor;

    public function __construct(public readonly int $broadcastId)
    {
        $this->afterCommit = true;
        $this->configureHortonQueue('broadcasts');
        $this->uniqueFor = $this->hortonQueueUniqueFor();
    }

    public function uniqueId(): string
    {
        return 'broadcast:'.$this->broadcastId;
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return $this->hortonQueueMiddleware();
    }

    public function handle(BroadcastService $broadcasts): void
    {
        $broadcast = Broadcast::query()->findOrFail($this->broadcastId);
        if ($broadcast->status === 'cancelled') return;

        $broadcast->forceFill([
            'status' => 'sending',
            'started_at' => $broadcast->started_at ?: now(),
        ])->save();

        $batchSize = min(1000, max(1, (int) ($broadcast->batch_size ?: 100)));
        $total = $broadcasts->recipients($broadcast)->count();
        $broadcast->forceFill(['total_recipients' => $total])->save();

        if ($total === 0) {
            $broadcast->forceFill(['status' => 'completed', 'completed_at' => now()])->save();
            return;
        }

        $broadcasts->recipients($broadcast)->chunkById($batchSize, function ($users) use ($broadcast): void {
            $rows = [];
            $now = now();
            foreach ($users as $user) {
                $rows[] = [
                    'broadcast_id' => $broadcast->id,
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'attempts' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('broadcast_recipients')->insertOrIgnore($rows);

            foreach ($users as $user) {
                SendBroadcastRecipientJob::dispatch($broadcast->id, (int) $user->id);
            }
        });
    }

    public function failed(Throwable $e): void
    {
        Broadcast::query()->whereKey($this->broadcastId)->whereNotIn('status', ['completed', 'cancelled'])->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }
}
