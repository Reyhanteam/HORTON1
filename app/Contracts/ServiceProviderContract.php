<?php

namespace App\Contracts;

use App\DTOs\ServiceProvider\ServiceProviderContext;
use App\DTOs\ServiceProvider\ServiceProviderResult;
use App\Models\Service;

interface ServiceProviderContract
{
    public function create(Service $service, ServiceProviderContext $context): ServiceProviderResult;
    public function get(Service $service, ServiceProviderContext $context): ServiceProviderResult;
    public function renew(Service $service, ServiceProviderContext $context, int $durationValue, string $durationUnit = 'day'): ServiceProviderResult;
    public function extend(Service $service, ServiceProviderContext $context, int $durationValue, string $durationUnit = 'day'): ServiceProviderResult;
    public function addCapacity(Service $service, ServiceProviderContext $context, int $amount): ServiceProviderResult;
    public function disable(Service $service, ServiceProviderContext $context): ServiceProviderResult;
    public function delete(Service $service, ServiceProviderContext $context): ServiceProviderResult;
    public function status(Service $service, ServiceProviderContext $context): ServiceProviderResult;
}
