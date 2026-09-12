<?php

namespace Tests\Feature;

use App\Contracts\ServiceLifecycle;
use App\Exceptions\DomainRuleViolation;
use App\Jobs\ProvisionServiceJob;
use App\Models\Plan;
use App\Models\Service;
use App\Models\ServiceOperation;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ServiceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_backed_lifecycle_updates_service_and_records_operations(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['duration_value' => 30, 'duration_unit' => 'day', 'capacity_value' => 10]);
        $provider = ServiceProvider::factory()->create(['driver' => 'fake', 'status' => 'active']);
        ServiceProviderAccount::factory()->create(['service_provider_id' => $provider->id, 'status' => 'active', 'priority' => 1]);
        $services = $this->app->make(ServiceLifecycle::class);

        $service = $services->create($user, $plan->id, $provider->id);
        $this->assertSame('pending', $service->status->value);
        $service = $services->provision($service);
        $this->assertSame('active', $service->status->value);
        $this->assertNotNull($service->external_id);
        $this->assertSame(1, ServiceOperation::query()->where('service_id', $service->id)->where('operation', 'create')->where('status', 'success')->count());

        $service = $services->extend($service, 7);
        $service = $services->extendCapacity($service, 5);
        $service = $services->status($service);
        $service = $services->get($service);
        $this->assertSame(15, $service->capacity);
        $this->assertGreaterThanOrEqual(5, ServiceOperation::query()->where('service_id', $service->id)->where('status', 'success')->count());
    }

    public function test_provisioning_is_idempotent_across_retries(): void
    {
        $service = $this->provisionableService();
        $services = $this->app->make(ServiceLifecycle::class);
        $first = $services->provision($service);
        $second = $services->provision($first);
        $this->assertSame($first->external_id, $second->external_id);
        $this->assertSame(1, ServiceOperation::query()->where('service_id', $service->id)->where('operation', 'create')->count());
    }

    public function test_provider_failure_marks_service_failed_and_persists_failure_history(): void
    {
        $service = $this->provisionableService(['fake_failure_operations' => ['create']]);
        $this->expectException(\App\Exceptions\ServiceProviderException::class);
        try {
            $this->app->make(ServiceLifecycle::class)->provision($service);
        } finally {
            $fresh = $service->refresh();
            $this->assertSame('failed', $fresh->status->value);
            $this->assertSame('failed', ServiceOperation::query()->where('service_id', $service->id)->latest('id')->value('status'));
        }
    }

    public function test_trial_is_limited_to_one_pending_or_active_service_per_user(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['is_trial' => true, 'trial_duration_value' => 3, 'trial_duration_unit' => 'day', 'duration_value' => 30]);
        $provider = ServiceProvider::factory()->create(['driver' => 'fake']);
        ServiceProviderAccount::factory()->create(['service_provider_id' => $provider->id]);
        $services = $this->app->make(ServiceLifecycle::class);
        $services->create($user, $plan->id, $provider->id);
        $this->expectException(DomainRuleViolation::class);
        $services->create($user, $plan->id, $provider->id);
    }

    public function test_invalid_capacity_is_rejected(): void
    {
        $service = $this->provisionableService();
        $this->expectException(DomainRuleViolation::class);
        $this->app->make(ServiceLifecycle::class)->extendCapacity($service, 0);
    }

    public function test_disable_and_delete_are_provider_backed_and_idempotent_at_local_state(): void
    {
        $service = $this->provisionableService();
        $services = $this->app->make(ServiceLifecycle::class);
        $service = $services->provision($service);
        $service = $services->disable($service);
        $this->assertSame('disabled', $service->status->value);
        $service = $services->disable($service);
        $this->assertSame('disabled', $service->status->value);
        $service = $service->refresh();
        $service->status = 'active';
        $service->save();
        $service = $services->delete($service);
        $this->assertSame('disabled', $service->status->value);
    }

    public function test_provision_job_is_queueable_with_retry_policy(): void
    {
        $service = $this->provisionableService();
        Queue::fake();
        ProvisionServiceJob::dispatch($service->id);
        Queue::assertPushed(ProvisionServiceJob::class, fn ($job) => $job->serviceId === $service->id && $job->tries === 3 && $job->timeout === 120 && $job->backoff() === [10, 30, 90]);
    }

    private function provisionableService(array $configuration = []): Service
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['duration_value' => 30, 'duration_unit' => 'day']);
        $provider = ServiceProvider::factory()->create(['driver' => 'fake', 'configuration' => $configuration]);
        ServiceProviderAccount::factory()->create(['service_provider_id' => $provider->id, 'status' => 'active']);
        return $this->app->make(ServiceLifecycle::class)->create($user, $plan->id, $provider->id);
    }
}
