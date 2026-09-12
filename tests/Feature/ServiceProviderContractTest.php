<?php

namespace Tests\Feature;

use App\Contracts\ServiceProviderContract;
use App\DTOs\ServiceProvider\ServiceProviderContext;
use App\Enums\ServiceProviderOperation;
use App\Exceptions\ServiceProviderException;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ServiceProviderContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_contract_is_bound_to_fake_provider(): void
    {
        $provider = $this->app->make(ServiceProviderContract::class);

        $this->assertInstanceOf(ServiceProviderContract::class, $provider);
    }

    public function test_fake_provider_completes_full_service_operation_contract(): void
    {
        $provider = ServiceProvider::factory()->create(['driver' => 'fake']);
        $account = ServiceProviderAccount::factory()->for($provider, 'provider')->create();
        $service = Service::factory()->create([
            'service_provider_id' => $provider->id,
            'provider_account_id' => $account->id,
            'capacity' => 10,
            'expires_at' => Carbon::now()->addDays(5),
        ]);
        $context = ServiceProviderContext::fromAccount($account);
        $driver = $this->app->make(ServiceProviderContract::class);

        $created = $driver->create($service, $context);
        $this->assertTrue($created->successful);
        $this->assertSame(ServiceProviderOperation::Create, $created->operation);
        $this->assertNotNull($created->externalId);

        $service->forceFill([
            'external_id' => $created->externalId,
            'external_reference' => $created->externalReference,
        ])->save();

        $fetched = $driver->get($service->refresh(), $context);
        $this->assertTrue($fetched->successful);
        $this->assertSame($created->externalId, $fetched->externalId);

        $renewed = $driver->renew($service, $context, 7);
        $this->assertTrue($renewed->successful);
        $this->assertSame('active', $renewed->status);
        $this->assertNotNull($renewed->expiresAt);

        $extended = $driver->extend($service, $context, 2, 'week');
        $this->assertTrue($extended->successful);
        $this->assertNotNull($extended->expiresAt);

        $capacity = $driver->addCapacity($service, $context, 5);
        $this->assertTrue($capacity->successful);
        $this->assertSame(15, $capacity->capacity);

        $disabled = $driver->disable($service, $context);
        $this->assertSame('disabled', $disabled->status);

        $status = $driver->status($service, $context);
        $this->assertTrue($status->successful);

        $deleted = $driver->delete($service, $context);
        $this->assertTrue($deleted->successful);
        $this->assertSame('deleted', $deleted->status);
    }

    public function test_create_is_idempotent_when_external_id_already_exists(): void
    {
        $provider = ServiceProvider::factory()->create();
        $account = ServiceProviderAccount::factory()->for($provider, 'provider')->create();
        $service = Service::factory()->provisioned()->create([
            'service_provider_id' => $provider->id,
            'provider_account_id' => $account->id,
        ]);
        $driver = $this->app->make(ServiceProviderContract::class);
        $context = ServiceProviderContext::fromAccount($account);

        $result = $driver->create($service, $context);

        $this->assertTrue($result->successful);
        $this->assertSame(ServiceProviderOperation::Get, $result->operation);
        $this->assertSame($service->external_id, $result->externalId);
    }

    public function test_provider_rules_reject_invalid_account_and_values(): void
    {
        $provider = ServiceProvider::factory()->create();
        $otherProvider = ServiceProvider::factory()->create();
        $account = ServiceProviderAccount::factory()->for($provider, 'provider')->create();
        $otherAccount = ServiceProviderAccount::factory()->for($otherProvider, 'provider')->create();
        $service = Service::factory()->provisioned()->create([
            'service_provider_id' => $provider->id,
            'provider_account_id' => $account->id,
        ]);
        $driver = $this->app->make(ServiceProviderContract::class);

        $this->expectException(ServiceProviderException::class);
        $this->expectExceptionMessage('Provider account does not belong');
        $driver->status($service, ServiceProviderContext::fromAccount($otherAccount));
    }

    public function test_provider_rules_reject_missing_external_id_and_invalid_duration(): void
    {
        $provider = ServiceProvider::factory()->create();
        $account = ServiceProviderAccount::factory()->for($provider, 'provider')->create();
        $service = Service::factory()->create([
            'service_provider_id' => $provider->id,
            'provider_account_id' => $account->id,
        ]);
        $driver = $this->app->make(ServiceProviderContract::class);
        $context = ServiceProviderContext::fromAccount($account);

        try {
            $driver->status($service, $context);
            $this->fail('Expected missing external ID exception.');
        } catch (ServiceProviderException $exception) {
            $this->assertSame('provider.external_id_missing', $exception->errorCode);
        }

        $service->update(['external_id' => 'fake-id']);

        $this->expectException(ServiceProviderException::class);
        $this->expectExceptionMessage('greater than zero');
        $driver->renew($service, $context, 0);
    }

    public function test_inactive_provider_account_and_provider_are_rejected(): void
    {
        $provider = ServiceProvider::factory()->create(['status' => 'inactive']);
        $account = ServiceProviderAccount::factory()->for($provider, 'provider')->create();
        $service = Service::factory()->provisioned()->create([
            'service_provider_id' => $provider->id,
            'provider_account_id' => $account->id,
        ]);
        $driver = $this->app->make(ServiceProviderContract::class);

        $this->expectException(ServiceProviderException::class);
        $this->expectExceptionMessage('not active');
        $driver->get($service, ServiceProviderContext::fromAccount($account));
    }

    public function test_fake_provider_can_simulate_external_failure(): void
    {
        $provider = ServiceProvider::factory()->create([
            'configuration' => ['fake_failure_operations' => ['create']],
        ]);
        $account = ServiceProviderAccount::factory()->for($provider, 'provider')->create();
        $service = Service::factory()->create([
            'service_provider_id' => $provider->id,
            'provider_account_id' => $account->id,
        ]);
        $driver = $this->app->make(ServiceProviderContract::class);

        $this->expectException(ServiceProviderException::class);
        $this->expectExceptionMessage('Fake provider failure');
        $driver->create($service, ServiceProviderContext::fromAccount($account));
    }
}
