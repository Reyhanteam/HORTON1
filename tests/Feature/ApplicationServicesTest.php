<?php

namespace Tests\Feature;

use App\Contracts\CashbackService;
use App\Contracts\CatalogService;
use App\Contracts\DiscountService;
use App\Contracts\GiftCodeService;
use App\Contracts\NotificationService;
use App\Contracts\OrderService;
use App\Contracts\PricingService;
use App\Contracts\ReferralService;
use App\Contracts\ServiceLifecycle;
use App\Contracts\SupportService;
use App\Contracts\WalletService;
use App\DTOs\CreateUserData;
use App\DTOs\NotificationData;
use App\DTOs\SupportTicketData;
use App\DTOs\WalletMutationData;
use App\Events\UserRegistered;
use App\Exceptions\DomainRuleViolation;
use App\Models\Category;
use App\Models\DiscountCode;
use App\Models\GiftCode;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ReferralAccount;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderAccount;
use App\Models\User;
use App\Services\Auth\UserLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ApplicationServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_contracts_are_bound(): void
    {
        foreach ([CatalogService::class,PricingService::class,OrderService::class,WalletService::class,DiscountService::class,GiftCodeService::class,ReferralService::class,CashbackService::class,ServiceLifecycle::class,NotificationService::class,SupportService::class] as $contract) $this->assertNotNull($this->app->make($contract));
    }

    public function test_registration_action_lifecycle_dispatches_event(): void
    {
        Event::fake([UserRegistered::class]);
        $user=$this->app->make(UserLifecycleService::class)->register((new CreateUserData(name:'Test User',username:'test-user'))->toArray());
        $this->assertSame('pending',$user->status->value); Event::assertDispatched(UserRegistered::class);
    }

    public function test_catalog_and_pricing_return_active_data_and_current_price(): void
    {
        $category=Category::factory()->create(); $product=Product::factory()->for($category)->create(); $plan=Plan::factory()->for($product)->create(['price'=>500000]);
        $this->assertTrue($this->app->make(CatalogService::class)->products($category)->contains($product)); $this->assertSame(500000,$this->app->make(PricingService::class)->price($plan));
    }

    public function test_order_service_creates_idempotent_order_and_invoice_on_payment(): void
    {
        $user=User::factory()->create(); $plan=Plan::factory()->create(['price'=>250000]); $orders=$this->app->make(OrderService::class);
        $first=$orders->create($user,[['plan_id'=>$plan->id,'quantity'=>2]],'order-key-1'); $second=$orders->create($user,[['plan_id'=>$plan->id]],'order-key-1');
        $this->assertTrue($first->is($second)); $this->assertSame(500000,$first->total_amount); $paid=$orders->markPaid($first);
        $this->assertSame('paid',$paid->status->value); $this->assertNotNull($paid->invoice);
    }

    public function test_wallet_ledger_prevents_negative_balance_and_supports_idempotency(): void
    {
        $user=User::factory()->create(); $wallet=$this->app->make(WalletService::class); $tx=$wallet->credit($user,new WalletMutationData(100000,'deposit',idempotencyKey:'wallet-1'));
        $same=$wallet->credit($user,new WalletMutationData(100000,'deposit',idempotencyKey:'wallet-1')); $this->assertTrue($tx->is($same)); $this->assertSame(100000,$tx->balance_after);
        $this->expectException(DomainRuleViolation::class); $wallet->debit($user,new WalletMutationData(100001,'purchase'));
    }

    public function test_discount_and_gift_rules_are_enforced(): void
    {
        $user=User::factory()->create(); $discount=DiscountCode::factory()->fixed(100000)->create(['minimum_order_amount'=>200000]); $discounts=$this->app->make(DiscountService::class);
        $valid=$discounts->validate($discount->code,$user,300000); $this->assertSame(100000,$discounts->calculate($valid,300000));
        $gift=GiftCode::factory()->create(['usage_limit'=>1]); $gifts=$this->app->make(GiftCodeService::class); $gifts->redeem($gifts->validate($gift->code,$user),$user); $this->assertSame(1,$gift->refresh()->used_count);
    }

    public function test_referral_and_cashback_services_update_domain_balances(): void
    {
        $referrer=User::factory()->create(); $referred=User::factory()->create(); ReferralAccount::query()->create(['user_id'=>$referrer->id,'code'=>'REF-TEST','commission_rate'=>0,'cashback_rate'=>0,'status'=>'active']);
        $referral=$this->app->make(ReferralService::class)->attach($referred,'REF-TEST'); $this->assertSame('registered',$referral->status);
        $cash=$this->app->make(CashbackService::class); $cash->earn($referred,50000,'order_cashback','order',1); $cash->redeem($referred,20000,'order_discount','order',2);
        $this->assertSame(30000,$referred->cashbackAccounts()->first()->balance);
    }

    public function test_service_lifecycle_applies_rules(): void
    {
        $user=User::factory()->create(); $plan=Plan::factory()->create(); $provider=ServiceProvider::factory()->create(['status'=>'active']); ServiceProviderAccount::factory()->create(['service_provider_id'=>$provider->id,'status'=>'active']); $services=$this->app->make(ServiceLifecycle::class);
        $service=$services->create($user,$plan->id,$provider->id); $this->assertSame('pending',$service->status->value); $service=$services->provision($service); $service=$services->renew($service,7);
        $this->assertSame('active',$service->status->value); $this->assertNotNull($service->expires_at);
    }

    public function test_notification_and_support_services_persist_records(): void
    {
        $user=User::factory()->create(); $notifications=$this->app->make(NotificationService::class); $notification=$notifications->create(new NotificationData($user->id,'system','Hello','Welcome'));
        $this->assertNull($notification->read_at); $notifications->markRead($notification); $this->assertNotNull($notification->refresh()->read_at);
        $ticket=$this->app->make(SupportService::class)->createTicket(new SupportTicketData($user->id,'Help','I need help.')); $this->assertSame(1,$ticket->messages()->count());
    }
}
