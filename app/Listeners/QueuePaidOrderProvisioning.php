<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Contracts\ServiceLifecycle;
use App\Events\OrderPaid;
use App\Jobs\Concerns\ConfiguresHortonQueue;
use App\Jobs\ProvisionServiceJob;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

final class QueuePaidOrderProvisioning implements ShouldQueue
{
    use ConfiguresHortonQueue;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Do not enqueue the listener until the OrderPaid transaction is committed. */
    public bool $afterCommit = true;

    public function __construct()
    {
        $this->configureHortonQueue();
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return $this->hortonQueueMiddleware();
    }

    public function handle(ServiceLifecycle $services, OrderPaid $event): void
    {
        $order = $event->order->fresh(['items']);
        if (! $order || $order->status->value !== 'paid') {
            return;
        }

        // Payment must not fail merely because provisioning is not configured yet.
        // A later provisioning workflow can pick the paid order up once a provider/account exists.
        $providerReady = ServiceProvider::query()
            ->where('status', 'active')
            ->whereHas('accounts', fn ($query) => $query->where('status', 'active'))
            ->exists();
        if (! $providerReady) {
            return;
        }

        $user = User::query()->findOrFail($order->user_id);
        foreach ($order->items as $item) {
            for ($index = 1; $index <= $item->quantity; $index++) {
                $exists = DB::table('services')
                    ->where('order_item_id', $item->id)
                    ->whereJsonContains('metadata', ['order_item_index' => $index])
                    ->exists();
                if ($exists) {
                    continue;
                }

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
