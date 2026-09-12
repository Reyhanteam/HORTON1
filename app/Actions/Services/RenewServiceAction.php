<?php

namespace App\Actions\Services;

use App\Contracts\ServiceLifecycle;
use App\Models\Service;

final class RenewServiceAction
{
    public function __construct(private readonly ServiceLifecycle $services) {}
    public function execute(Service $service,int $durationValue,string $durationUnit='day'): Service { return $this->services->renew($service,$durationValue,$durationUnit); }
}
