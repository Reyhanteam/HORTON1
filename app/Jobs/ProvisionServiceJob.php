<?php

namespace App\Jobs;

use App\Contracts\ServiceLifecycle;
use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProvisionServiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function __construct(public readonly int $serviceId) {}

    public function handle(ServiceLifecycle $services): void
    {
        $service = Service::query()->findOrFail($this->serviceId);
        if ($service->external_id && $service->status->value === 'active') return;
        $services->provision($service);
    }
}
