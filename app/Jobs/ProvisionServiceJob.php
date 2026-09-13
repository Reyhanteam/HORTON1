<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\ServiceLifecycle;
use App\Jobs\Concerns\ConfiguresHortonQueue;
use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProvisionServiceJob implements ShouldQueue, ShouldBeUnique
{
    use ConfiguresHortonQueue;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $uniqueFor;

    public function __construct(public readonly int $serviceId)
    {
        $this->configureHortonQueue();
        $this->uniqueFor = $this->hortonQueueUniqueFor();
    }

    public function uniqueId(): string
    {
        return 'service-provision:' . $this->serviceId;
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return $this->hortonQueueMiddleware();
    }

    public function handle(ServiceLifecycle $services): void
    {
        $service = Service::query()->findOrFail($this->serviceId);
        if ($service->external_id && $service->status->value === 'active') {
            return;
        }

        $services->provision($service);
    }
}
