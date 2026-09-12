<?php

namespace App\Services\Service;

use App\Contracts\ServiceLifecycle;
use App\Contracts\ServiceProviderContract;
use App\DTOs\ServiceProvider\ServiceProviderContext;
use App\DTOs\ServiceProvider\ServiceProviderResult;
use App\Enums\ServiceProviderOperation;
use App\Enums\ServiceStatus;
use App\Exceptions\DomainRuleViolation;
use App\Exceptions\ServiceProviderException;
use App\Models\Plan;
use App\Models\Service;
use App\Models\ServiceOperation;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderAccount;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

abstract class AbstractServiceLifecycle implements ServiceLifecycle
{
    public function __construct(protected readonly ServiceProviderContract $provider) {}

    final public function create(User $user, int $planId, ?int $providerId = null, array $attributes = []): Service
    {
        return DB::transaction(function () use ($user, $planId, $providerId, $attributes): Service {
            $plan = Plan::query()->with('product')->lockForUpdate()->findOrFail($planId);
            $this->assertPlan($plan);
            $provider = $this->selectProvider($providerId);
            $account = $this->selectAccount($provider);
            $trial = (bool) $plan->is_trial;
            if ($trial && Service::query()->where('user_id', $user->id)->where('is_trial', true)->whereIn('status', [ServiceStatus::PENDING, ServiceStatus::ACTIVE])->exists()) {
                throw new DomainRuleViolation('User already has an active or pending trial service.', 'service.trial_already_used');
            }
            $durationValue = $trial ? (int) $plan->trial_duration_value : (int) $plan->duration_value;
            $durationUnit = $trial ? ($plan->trial_duration_unit ?: $plan->duration_unit) : $plan->duration_unit;
            if ($durationValue <= 0) throw new DomainRuleViolation('Service duration must be positive.', 'service.duration');

            $service = Service::query()->create(array_merge([
                'uuid' => (string) Str::uuid(), 'user_id' => $user->id, 'plan_id' => $plan->id,
                'service_provider_id' => $provider->id, 'provider_account_id' => $account->id,
                'status' => ServiceStatus::PENDING, 'starts_at' => null, 'expires_at' => null,
                'capacity' => $plan->capacity_value, 'used_capacity' => 0, 'is_trial' => $trial,
                'metadata' => ['duration_value' => $durationValue, 'duration_unit' => $durationUnit],
            ], $attributes));
            return $service->refresh();
        });
    }

    final public function provision(Service $service): Service
    {
        return $this->runProviderOperation($service, ServiceProviderOperation::Create, fn (Service $locked, ServiceProviderContext $context) => $this->provider->create($locked, $context), function (Service $locked, ServiceProviderResult $result): void {
            $locked->forceFill([
                'status' => ServiceStatus::ACTIVE, 'external_id' => $result->externalId ?: $locked->external_id,
                'external_reference' => $result->externalReference ?: $locked->external_reference,
                'starts_at' => $locked->starts_at ?: now(), 'expires_at' => $result->expiresAt ?: $this->calculateExpiry($locked),
                'capacity' => $result->capacity ?? $locked->capacity, 'used_capacity' => $result->usedCapacity ?? $locked->used_capacity,
                'metadata' => array_merge($locked->metadata ?? [], $result->metadata),
            ])->save();
        }, 'service-provision-'.$service->id);
    }

    final public function get(Service $service): Service
    {
        return $this->runProviderOperation($service, ServiceProviderOperation::Get, fn ($locked, $context) => $this->provider->get($locked, $context), fn ($locked, $result) => $this->applyProviderState($locked, $result));
    }

    final public function status(Service $service): Service
    {
        return $this->runProviderOperation($service, ServiceProviderOperation::Status, fn ($locked, $context) => $this->provider->status($locked, $context), fn ($locked, $result) => $this->applyProviderState($locked, $result));
    }

    final public function renew(Service $service, int $durationValue, string $durationUnit = 'day'): Service
    {
        $this->assertDuration($durationValue, $durationUnit);
        return $this->runProviderOperation($service, ServiceProviderOperation::Renew, fn ($locked, $context) => $this->provider->renew($locked, $context, $durationValue, $durationUnit), function ($locked, $result) use ($durationValue, $durationUnit): void {
            $locked->forceFill(['status' => ServiceStatus::ACTIVE, 'expires_at' => $result->expiresAt ?: $this->futureExpiry($locked)->add($durationValue, $durationUnit)])->save();
        });
    }

    final public function extend(Service $service, int $durationValue, string $durationUnit = 'day'): Service
    {
        $this->assertDuration($durationValue, $durationUnit);
        return $this->runProviderOperation($service, ServiceProviderOperation::Extend, fn ($locked, $context) => $this->provider->extend($locked, $context, $durationValue, $durationUnit), function ($locked, $result) use ($durationValue, $durationUnit): void {
            $locked->forceFill(['status' => ServiceStatus::ACTIVE, 'expires_at' => $result->expiresAt ?: $this->futureExpiry($locked)->add($durationValue, $durationUnit)])->save();
        });
    }

    final public function extendCapacity(Service $service, int $amount): Service
    {
        if ($amount <= 0) throw new DomainRuleViolation('Capacity extension must be positive.', 'service.capacity');
        return $this->runProviderOperation($service, ServiceProviderOperation::AddCapacity, fn ($locked, $context) => $this->provider->addCapacity($locked, $context, $amount), function ($locked, $result) use ($amount): void {
            $locked->forceFill(['capacity' => $result->capacity ?? ((int) $locked->capacity + $amount), 'used_capacity' => $result->usedCapacity ?? $locked->used_capacity])->save();
        });
    }

    final public function disable(Service $service): Service
    {
        if ($service->status === ServiceStatus::DISABLED) return $service->refresh();
        return $this->runProviderOperation($service, ServiceProviderOperation::Disable, fn ($locked, $context) => $this->provider->disable($locked, $context), function ($locked): void {
            $locked->forceFill(['status' => ServiceStatus::DISABLED])->save();
        });
    }

    final public function delete(Service $service): Service
    {
        return $this->runProviderOperation($service, ServiceProviderOperation::Delete, fn ($locked, $context) => $this->provider->delete($locked, $context), function ($locked): void {
            $locked->forceFill(['status' => ServiceStatus::DISABLED, 'metadata' => array_merge($locked->metadata ?? [], ['provider_deleted_at' => now()->toIso8601String()])])->save();
        });
    }

    private function runProviderOperation(Service $service, ServiceProviderOperation $operation, callable $call, ?callable $apply = null, ?string $idempotencyKey = null): Service
    {
        try {
            return DB::transaction(function () use ($service, $operation, $call, $apply, $idempotencyKey): Service {
                $locked = Service::query()->with(['provider', 'providerAccount', 'plan'])->whereKey($service->id)->lockForUpdate()->firstOrFail();
                $this->assertService($locked);
                $operationRow = $this->startOperation($locked, $operation, $idempotencyKey);
                if ($operationRow->status === 'success') return $locked->refresh();
                try {
                    $result = $call($locked, ServiceProviderContext::fromAccount($locked->providerAccount));
                    if (! $result->successful) throw new ServiceProviderException($result->errorMessage ?: 'Provider operation failed.', $result->errorCode ?: 'provider.operation_failed');
                    if ($apply) $apply($locked, $result);
                    $operationRow->forceFill(['status' => 'success', 'completed_at' => now(), 'response_metadata' => $this->resultMetadata($result)])->save();
                    return $locked->refresh();
                } catch (Throwable $e) { throw $e; }
            });
        } catch (Throwable $e) {
            $this->recordFailure($service, $operation, $e, $idempotencyKey);
            throw $e;
        }
    }

    private function recordFailure(Service $service, ServiceProviderOperation $operation, Throwable $e, ?string $idempotencyKey): void
    {
        DB::transaction(function () use ($service, $operation, $e, $idempotencyKey): void {
            $locked = Service::query()->whereKey($service->id)->lockForUpdate()->first();
            if (! $locked) return;
            $existing = $idempotencyKey ? ServiceOperation::query()->where('idempotency_key', $idempotencyKey)->first() : null;
            $row = $existing ?: ServiceOperation::query()->create(['service_id' => $locked->id, 'operation' => $operation->value, 'status' => 'failed', 'idempotency_key' => $idempotencyKey ?: 'service-failed-'.$locked->id.'-'.Str::uuid(), 'started_at' => now()]);
            $row->forceFill(['status' => 'failed', 'completed_at' => now(), 'error_code' => (string) $e->getCode() ?: 'provider.error', 'error_message' => $e->getMessage()])->save();
            $locked->forceFill(['status' => ServiceStatus::FAILED])->save();
        });
    }

    private function startOperation(Service $service, ServiceProviderOperation $operation, ?string $idempotencyKey): ServiceOperation
    {
        if ($idempotencyKey) {
            $existing = ServiceOperation::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
            if ($existing) return $existing;
        }
        return ServiceOperation::query()->create(['service_id' => $service->id, 'operation' => $operation->value, 'status' => 'running', 'idempotency_key' => $idempotencyKey ?: 'service-'.$service->id.'-'.$operation->value.'-'.Str::uuid(), 'started_at' => now(), 'request_metadata' => ['provider_id' => $service->service_provider_id, 'account_id' => $service->provider_account_id]]);
    }

    private function applyProviderState(Service $service, ServiceProviderResult $result): void
    {
        $attributes = ['external_id' => $result->externalId ?: $service->external_id, 'external_reference' => $result->externalReference ?: $service->external_reference];
        if ($result->status !== null && $result->status !== 'deleted') $attributes['status'] = ServiceStatus::tryFrom($result->status) ?: $service->status;
        if ($result->expiresAt) $attributes['expires_at'] = $result->expiresAt;
        if ($result->capacity !== null) $attributes['capacity'] = $result->capacity;
        if ($result->usedCapacity !== null) $attributes['used_capacity'] = $result->usedCapacity;
        $attributes['metadata'] = array_merge($service->metadata ?? [], $result->metadata);
        $service->forceFill($attributes)->save();
    }

    private function assertPlan(Plan $plan): void
    {
        if ($plan->status !== 'active' || $plan->duration_value <= 0) throw new DomainRuleViolation('Plan is not purchasable for service creation.', 'service.plan_invalid');
        if ($plan->is_trial && ((int) $plan->trial_duration_value <= 0 || blank($plan->trial_duration_unit))) throw new DomainRuleViolation('Trial plan has an invalid trial duration.', 'service.trial_invalid');
    }

    private function selectProvider(?int $providerId): ServiceProvider
    {
        $query = ServiceProvider::query()->where('status', 'active')->orderBy('id');
        if ($providerId !== null) $query->whereKey($providerId);
        $provider = $query->first();
        if (! $provider) throw new DomainRuleViolation('No active service provider is available.', 'service.provider_unavailable');
        return $provider;
    }

    private function selectAccount(ServiceProvider $provider): ServiceProviderAccount
    {
        $account = $provider->accounts()->where('status', 'active')->orderBy('priority')->orderBy('id')->lockForUpdate()->first();
        if (! $account) throw new DomainRuleViolation('No active provider account is available.', 'service.provider_account_unavailable');
        return $account;
    }

    private function assertService(Service $service): void
    {
        if (! $service->providerAccount) throw new DomainRuleViolation('Service has no provider account.', 'service.provider_account_missing');
        if ((int) $service->providerAccount->service_provider_id !== (int) $service->service_provider_id) throw new DomainRuleViolation('Service provider account mismatch.', 'service.provider_account_mismatch');
        if (! $service->provider || $service->provider->status !== 'active') throw new DomainRuleViolation('Service provider is inactive.', 'service.provider_inactive');
        if ($service->providerAccount->status !== 'active') throw new DomainRuleViolation('Service provider account is inactive.', 'service.provider_account_inactive');
    }

    private function assertDuration(int $value, string $unit): void
    {
        if ($value <= 0 || ! in_array($unit, ['hour', 'day', 'week', 'month', 'year'], true)) throw new DomainRuleViolation('Invalid service duration.', 'service.duration');
    }

    private function calculateExpiry(Service $service): Carbon
    {
        $value = (int) data_get($service->metadata, 'duration_value', $service->plan?->duration_value ?? 0);
        $unit = (string) data_get($service->metadata, 'duration_unit', $service->plan?->duration_unit ?? 'day');
        return now()->add($value.' '.$unit);
    }

    private function futureExpiry(Service $service): Carbon
    {
        return $service->expires_at?->isFuture() ? $service->expires_at->copy() : now();
    }

    private function resultMetadata(ServiceProviderResult $result): array
    {
        return array_filter(['external_id' => $result->externalId, 'external_reference' => $result->externalReference, 'status' => $result->status, 'expires_at' => $result->expiresAt?->toIso8601String(), 'capacity' => $result->capacity, 'used_capacity' => $result->usedCapacity, 'metadata' => $result->metadata], static fn ($value) => $value !== null);
    }
}
