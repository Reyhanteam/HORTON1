<?php

namespace App\Listeners;

use App\Contracts\ServiceLifecycle;
use App\Events\OrderPaid;
use App\Jobs\ProvisionServiceJob;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

final class QueuePaidOrderProvisioning implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function handle(ServiceLifecycle $services, OrderPaid $event): void
    {
        $order = $event->order->fresh(['items']);
        if (! $order || $order->status->value !== 'paid') return;
        $user = User::query()->findOrFail($order->user_id);

        foreach ($order->items as $item) {
            for ($index = 1; $index <= $item->quantity; $index++) {
                $exists = DB::table('services')
                    ->where('order_item_id', $item->id)
                    ->whereJsonContains('metadata', ['order_item_index' => $index])
                    ->exists();
                if ($exists) continue;

                $service = $services->create($user, (int) $item->plan_id, null, [
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'metadata' => ['order_item_index' => $index, 'provisioning_source' => 'order_paid'],
                ]);
                ProvisionServiceJob::dispatch($service->id);
            }
        }
    }
}
