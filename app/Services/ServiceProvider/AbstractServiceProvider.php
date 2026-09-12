<?php

namespace App\Services\ServiceProvider;

use App\Contracts\ServiceProviderContract;
use App\DTOs\ServiceProvider\ServiceProviderContext;
use App\DTOs\ServiceProvider\ServiceProviderResult;
use App\Exceptions\ServiceProviderException;
use App\Models\Service;

abstract class AbstractServiceProvider implements ServiceProviderContract
{
    final public function create(Service $service, ServiceProviderContext $context): ServiceProviderResult
    {
        $this->assertReady($service, $context);

        // A persisted external ID means provisioning already reached the provider.
        // Reconcile instead of creating a second remote resource.
        if (filled($service->external_id)) {
            return $this->performGet($service, $context);
        }

        return $this->performCreate($service, $context);
    }

    final public function get(Service $service, ServiceProviderContext $context): ServiceProviderResult
    {
        $this->assertReady($service, $context);
        $this->requireExternalId($service);
        return $this->performGet($service, $context);
    }

    final public function renew(Service $service, ServiceProviderContext $context, int $durationValue, string $durationUnit = 'day'): ServiceProviderResult
    {
        $this->assertReady($service, $context);
        $this->requirePositive($durationValue, 'provider.duration');
        $this->requireDurationUnit($durationUnit);
        $this->requireExternalId($service);
        return $this->performRenew($service, $context, $durationValue, $durationUnit);
    }

    final public function extend(Service $service, ServiceProviderContext $context, int $durationValue, string $durationUnit = 'day'): ServiceProviderResult
    {
        $this->assertReady($service, $context);
        $this->requirePositive($durationValue, 'provider.duration');
        $this->requireDurationUnit($durationUnit);
        $this->requireExternalId($service);
        return $this->performExtend($service, $context, $durationValue, $durationUnit);
    }

    final public function addCapacity(Service $service, ServiceProviderContext $context, int $amount): ServiceProviderResult
    {
        $this->assertReady($service, $context);
        $this->requirePositive($amount, 'provider.capacity');
        $this->requireExternalId($service);
        return $this->performAddCapacity($service, $context, $amount);
    }

    final public function disable(Service $service, ServiceProviderContext $context): ServiceProviderResult
    {
        $this->assertReady($service, $context);
        $this->requireExternalId($service);
        return $this->performDisable($service, $context);
    }

    final public function delete(Service $service, ServiceProviderContext $context): ServiceProviderResult
    {
        $this->assertReady($service, $context);
        $this->requireExternalId($service);
        return $this->performDelete($service, $context);
    }

    final public function status(Service $service, ServiceProviderContext $context): ServiceProviderResult
    {
        $this->assertReady($service, $context);
        $this->requireExternalId($service);
        return $this->performStatus($service, $context);
    }

    protected function assertReady(Service $service, ServiceProviderContext $context): void
    {
        $provider = $context->account->provider()->firstOrFail();

        if ($provider->status !== 'active') {
            throw new ServiceProviderException('Service provider is not active.', 'provider.inactive');
        }

        if ($context->account->status !== 'active') {
            throw new ServiceProviderException('Service provider account is not active.', 'provider.account_inactive');
        }

        if ((int) $context->account->service_provider_id !== (int) $service->service_provider_id) {
            throw new ServiceProviderException('Provider account does not belong to the service provider.', 'provider.account_mismatch');
        }
    }

    protected function requireExternalId(Service $service): void
    {
        if (blank($service->external_id)) {
            throw new ServiceProviderException('Service does not have an external provider ID.', 'provider.external_id_missing');
        }
    }

    protected function requirePositive(int $value, string $code): void
    {
        if ($value <= 0) {
            throw new ServiceProviderException('Value must be greater than zero.', $code);
        }
    }

    protected function requireDurationUnit(string $unit): void
    {
        if (! in_array($unit, ['hour', 'day', 'week', 'month', 'year'], true)) {
            throw new ServiceProviderException('Unsupported duration unit.', 'provider.duration_unit');
        }
    }

    abstract protected function performCreate(Service $service, ServiceProviderContext $context): ServiceProviderResult;
    abstract protected function performGet(Service $service, ServiceProviderContext $context): ServiceProviderResult;
    abstract protected function performRenew(Service $service, ServiceProviderContext $context, int $durationValue, string $durationUnit): ServiceProviderResult;
    abstract protected function performExtend(Service $service, ServiceProviderContext $context, int $durationValue, string $durationUnit): ServiceProviderResult;
    abstract protected function performAddCapacity(Service $service, ServiceProviderContext $context, int $amount): ServiceProviderResult;
    abstract protected function performDisable(Service $service, ServiceProviderContext $context): ServiceProviderResult;
    abstract protected function performDelete(Service $service, ServiceProviderContext $context): ServiceProviderResult;
    abstract protected function performStatus(Service $service, ServiceProviderContext $context): ServiceProviderResult;
}
