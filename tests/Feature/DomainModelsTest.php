<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\GiftCodeType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Enums\TransactionDirection;
use App\Models\Category;
use App\Models\DiscountCode;
use App\Models\GiftCode;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_and_plan_factories_build_the_shop_graph(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $plan = Plan::factory()->for($product)->create();
        $price = PlanPrice::factory()->for($plan)->create();

        $this->assertTrue($product->category->is($category));
        $this->assertTrue($category->products->contains($product));
        $this->assertTrue($plan->product->is($product));
        $this->assertTrue($product->plans->contains($plan));
        $this->assertTrue($price->plan->is($plan));
        $this->assertTrue($plan->prices->contains($price));
    }

    public function test_user_order_relationship_is_bidirectional(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();

        $this->assertTrue($order->user->is($user));
        $this->assertTrue($user->orders->contains($order));
    }

    public function test_trial_plan_factory_sets_trial_attributes(): void
    {
        $plan = Plan::factory()->trial()->create();

        $this->assertTrue($plan->is_trial);
        $this->assertSame(0, $plan->price);
        $this->assertSame(3, $plan->trial_duration_value);
        $this->assertSame('day', $plan->trial_duration_unit);
    }

    public function test_discount_and_gift_factories_build_marketing_codes(): void
    {
        $discount = DiscountCode::factory()->fixed(250_000)->create();
        $gift = GiftCode::factory()->discount(150_000)->create();

        $this->assertSame(DiscountType::FIXED, $discount->type);
        $this->assertSame(250_000, $discount->value);
        $this->assertTrue($discount->is_active);
        $this->assertSame(GiftCodeType::DISCOUNT, $gift->type);
        $this->assertSame(150_000, $gift->value);
        $this->assertTrue($gift->is_active);
    }

    public function test_domain_status_and_type_casts_use_backed_enums(): void
    {
        $order = new Order(['status' => 'paid']);
        $payment = new Payment(['status' => 'success']);
        $service = new Service(['status' => 'active']);
        $discount = new DiscountCode(['type' => 'percentage']);
        $gift = new GiftCode(['type' => 'credit']);
        $walletTransaction = new WalletTransaction(['direction' => 'credit']);

        $this->assertSame(OrderStatus::PAID, $order->status);
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertSame(ServiceStatus::ACTIVE, $service->status);
        $this->assertSame(DiscountType::PERCENTAGE, $discount->type);
        $this->assertSame(GiftCodeType::CREDIT, $gift->type);
        $this->assertSame(TransactionDirection::CREDIT, $walletTransaction->direction);
    }

    public function test_enum_casts_store_backing_values(): void
    {
        $order = new Order;
        $payment = new Payment;
        $service = new Service;
        $discount = new DiscountCode;
        $gift = new GiftCode;
        $walletTransaction = new WalletTransaction;

        $order->status = OrderStatus::COMPLETED;
        $payment->status = PaymentStatus::PENDING;
        $service->status = ServiceStatus::EXPIRED;
        $discount->type = DiscountType::FIXED;
        $gift->type = GiftCodeType::DISCOUNT;
        $walletTransaction->direction = TransactionDirection::DEBIT;

        $this->assertSame('completed', $order->getAttributes()['status']);
        $this->assertSame('pending', $payment->getAttributes()['status']);
        $this->assertSame('expired', $service->getAttributes()['status']);
        $this->assertSame('fixed', $discount->getAttributes()['type']);
        $this->assertSame('discount', $gift->getAttributes()['type']);
        $this->assertSame('debit', $walletTransaction->getAttributes()['direction']);
    }
}
