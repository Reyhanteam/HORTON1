<?php

namespace App\Services\ServiceProvider;

use App\DTOs\ServiceProvider\ServiceProviderContext;
use App\DTOs\ServiceProvider\ServiceProviderResult;
use App\Enums\ServiceProviderOperation;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class FakeServiceProvider extends AbstractServiceProvider
{
    protected function performCreate(Service $service, ServiceProviderContext $context): ServiceProviderResult
    {
        $this->failIfConfigured($context, ServiceProviderOperation::Create);

        $externalId = 'fake-'.Str::lower(Str::random(20));
        $reference = 'fake-ref-'.Str::lower(Str::random(16));

        return ServiceProviderResult::success(ServiceProviderOperation::Create, [
            'externalId' => $externalId,
            'externalReference' => $reference,
            'status' => 'active',
            'expiresAt' => $service->expires_at?->copy(),
            'capacity' => (int) ($service->capacity ?? 0),
            'usedCapacity' => (int) $service->used_capacity,
            'metadata' => ['driver' => 'fake'],
        ]);
    }

    protected function performGet(Service $service, ServiceProviderContext $context): ServiceProviderResult
    {
        $this->failIfConfigured($context, ServiceProviderOperation::Get);

        return ServiceProviderResult::success(ServiceProviderOperation::Get, [
            'externalId' => $service->external_id,
            'externalReference' => $service->external_reference,
            'status' => $service->status?->value ?? 'unknown',
            'expiresAt' => $service->expires_at?->copy(),
            'capacity' => (int) ($service->capacity ?? 0),
            'usedCapacity' => (int) $service->used_capacity,
            'metadata' => ['driver' => 'fake'],
        ]);
    }

    protected function performRenew(Service $service, ServiceProviderContext $context, int $durationValue, string $durationUnit): ServiceProviderResult
    {
        $this->failIfConfigured($context, ServiceProviderOperation::Renew);
        $expiresAt = $this->futureExpiry($service)->add($durationValue, $durationUnit);

        return ServiceProviderResult::success(ServiceProviderOperation::Renew, [
            'externalId' => $service->external_id,
            'externalReference' => $service->external_reference,
            'status' => 'active',
            'expiresAt' => $expiresAt,
            'capacity' => (int) ($service->capacity ?? 0),
            'usedCapacity' => (int) $service->used_capacity,
        ]);
    }

    protected function performExtend(Service $service, ServiceProviderContext $context, int $durationValue, string $durationUnit): ServiceProviderResult
    {
        $this->failIfConfigured($context, ServiceProviderOperation::Extend);
        $expiresAt = $this->futureExpiry($service)->add($durationValue, $durationUnit);

        return ServiceProviderResult::success(ServiceProviderOperation::Extend, [
            'externalId' => $service->external_id,
            'externalReference' => $service->external_reference,
            'status' => 'active',
            'expiresAt' => $expiresAt,
        ]);
    }

    protected function performAddCapacity(Service $service, ServiceProviderContext $context, int $amount): ServiceProviderResult
    {
        $this->failIfConfigured($context, ServiceProviderOperation::AddCapacity);

        return ServiceProviderResult::success(ServiceProviderOperation::AddCapacity, [
            'externalId' => $service->external_id,
            'externalReference' => $service->external_reference,
            'status' => $service->status?->value ?? 'active',
            'capacity' => (int) ($service->capacity ?? 0) + $amount,
            'usedCapacity' => (int) $service->used_capacity,
        ]);
    }

    protected function performDisable(Service $service, ServiceProviderContext $context): ServiceProviderResult
    {
        $this->failIfConfigured($context, ServiceProviderOperation::Disable);

        return ServiceProviderResult::success(ServiceProviderOperation::Disable, [
            'externalId' => $service->external_id,
            'externalReference' => $service->external_reference,
            'status' => 'disabled',
        ]);
    }

    protected function performDelete(Service $service, ServiceProviderContext $context): ServiceProviderResult
    {
        $this->failIfConfigured($context, ServiceProviderOperation::Delete);

        return ServiceProviderResult::success(ServiceProviderOperation::Delete, [
            'externalId' => $service->external_id,
            'externalReference' => $service->external_reference,
            'status' => 'deleted',
        ]);
    }

    protected function performStatus(Service $service, ServiceProviderContext $context): ServiceProviderResult
    {
        $this->failIfConfigured($context, ServiceProviderOperation::Status);

        return ServiceProviderResult::success(ServiceProviderOperation::Status, [
            'externalId' => $service->external_id,
            'externalReference' => $service->external_reference,
            'status' => $service->status?->value ?? 'unknown',
            'expiresAt' => $service->expires_at?->copy(),
            'capacity' => (int) ($service->capacity ?? 0),
            'usedCapacity' => (int) $service->used_capacity,
        ]);
    }

    private function futureExpiry(Service $service): Carbon
    {
        return $service->expires_at?->isFuture() ? $service->expires_at->copy() : Carbon::now();
    }

    private function failIfConfigured(ServiceProviderContext $context, ServiceProviderOperation $operation): void
    {
        $failed = $context->configuration['fake_failure_operations'] ?? [];
        if (in_array($operation->value, $failed, true)) {
            throw new \App\Exceptions\ServiceProviderException(
                'Fake provider failure configured for operation.',
                'provider.fake_failure'
            );
        }
    }
}
