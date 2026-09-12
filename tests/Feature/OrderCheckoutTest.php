<?php

namespace Tests\Feature;

use App\Contracts\CheckoutService;
use App\Contracts\OrderService;
use App\Contracts\OrderStateMachine;
use App\DTOs\CheckoutData;
use App\DTOs\OrderItemData;
use App\Enums\OrderStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_checkout_and_invoice_are_atomic_and_snapshot_catalog_data(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Original Category']);
        $product = Product::factory()->for($category)->create(['name' => 'Original Product']);
        $plan = Plan::factory()->for($product)->create(['name' => 'Original Plan', 'price' => 250000, 'duration_value' => 30, 'duration_unit' => 'day', 'capacity_value' => 50, 'capacity_unit' => 'GB']);

        $result = $this->app->make(CheckoutService::class)->checkout($user, new CheckoutData([new OrderItemData($plan->id, 2)], 'IRR', 'checkout-1'));
        $item = $result->order->items()->first();

        $this->assertTrue($result->order->is($result->invoice->order));
        $this->assertSame(500000, $result->order->total_amount);
        $this->assertSame(500000, $result->invoice->total_amount);
        $this->assertSame('Original Product', $item->product_name_snapshot);
        $this->assertSame('Original Plan', $item->plan_name_snapshot);
        $this->assertSame(30, $item->duration_value_snapshot);
        $this->assertSame(50, $item->capacity_value_snapshot);

        $plan->update(['name' => 'Changed Plan', 'price' => 999999, 'duration_value' => 7]);
        $historical = $result->order->items()->firstOrFail();
        $this->assertSame(250000, $historical->unit_price);
        $this->assertSame('Original Plan', $historical->plan_name_snapshot);
        $this->assertSame(30, $historical->duration_value_snapshot);
    }

    public function test_catalog_price_is_rechecked_and_client_cannot_override_it(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 100000]);
        $this->expectException(DomainRuleViolation::class);
        $this->expectExceptionMessage('Client price does not match');
        $this->app->make(OrderService::class)->create($user, [new OrderItemData($plan->id, 1, 99999)], 'price-mismatch');
    }

    public function test_order_idempotency_returns_same_order_and_rejects_cross_user_reuse(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 120000]);
        $orders = $this->app->make(OrderService::class);
        $first = $orders->create($firstUser, [new OrderItemData($plan->id)], 'same-key');
        $same = $orders->create($firstUser, [new OrderItemData($plan->id, 9)], 'same-key');
        $this->assertTrue($first->is($same));
        $this->expectException(DomainRuleViolation::class);
        $orders->create($secondUser, [new OrderItemData($plan->id)], 'same-key');
    }

    public function test_order_state_machine_enforces_allowed_transitions(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $order = $this->app->make(OrderService::class)->create($user, [new OrderItemData($plan->id)], 'state-key');
        $states = $this->app->make(OrderStateMachine::class);
        $this->assertTrue($states->canTransition(OrderStatus::PENDING, OrderStatus::PAID));
        $this->assertFalse($states->canTransition(OrderStatus::PENDING, OrderStatus::COMPLETED));
        $this->app->make(OrderService::class)->transition($order, OrderStatus::PAID);
        $this->expectException(DomainRuleViolation::class);
        $this->app->make(OrderService::class)->transition($order->refresh(), OrderStatus::COMPLETED);
    }

    public function test_cancelled_order_cannot_be_paid(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $orders = $this->app->make(OrderService::class);
        $cancelled = $orders->create($user, [new OrderItemData($plan->id)], 'cancel-key');
        $orders->cancel($cancelled);
        $this->expectException(DomainRuleViolation::class);
        $orders->markPaid($cancelled);
    }

    public function test_completed_order_cannot_be_cancelled(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $orders = $this->app->make(OrderService::class);
        $completed = $orders->create($user, [new OrderItemData($plan->id)], 'complete-key');
        $orders->markPaid($completed);
        $orders->transition($completed, OrderStatus::PROCESSING);
        $orders->transition($completed, OrderStatus::COMPLETED);
        $this->expectException(DomainRuleViolation::class);
        $orders->cancel($completed->refresh());
    }

    public function test_mark_paid_is_idempotent_and_marks_invoice_paid(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 200000]);
        $orders = $this->app->make(OrderService::class);
        $order = $orders->create($user, [new OrderItemData($plan->id)], 'paid-key');
        $first = $orders->markPaid($order);
        $second = $orders->markPaid($order);
        $this->assertTrue($first->is($second));
        $this->assertSame('paid', $second->invoice->status);
        $this->assertNotNull($second->paid_at);
        $this->assertNotNull($second->invoice->paid_at);
        $this->assertSame(1, $second->invoice()->count());
    }

    public function test_purchase_history_is_scoped_to_user_and_paginated(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $plan = Plan::factory()->create();
        $orders = $this->app->make(OrderService::class);
        $orders->create($user, [new OrderItemData($plan->id)], 'history-1');
        $orders->create($user, [new OrderItemData($plan->id)], 'history-2');
        $orders->create($other, [new OrderItemData($plan->id)], 'history-other');
        $history = $orders->history($user, 1);
        $this->assertSame(2, $history->total());
        $this->assertCount(1, $history->items());
        $this->assertTrue(collect($history->items())->every(fn ($order) => $order->user_id === $user->id));
    }

    public function test_checkout_reuses_idempotent_order_without_creating_second_invoice(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['price' => 175000]);
        $checkout = $this->app->make(CheckoutService::class);
        $first = $checkout->checkout($user, new CheckoutData([new OrderItemData($plan->id)], 'IRR', 'checkout-replay'));
        $second = $checkout->checkout($user, new CheckoutData([new OrderItemData($plan->id, 3)], 'IRR', 'checkout-replay'));
        $this->assertTrue($first->order->is($second->order));
        $this->assertTrue($first->invoice->is($second->invoice));
        $this->assertSame(1, $user->orders()->where('idempotency_key', 'checkout-replay')->count());
        $this->assertSame(1, $first->order->items()->count());
    }

    public function test_checkout_rejects_invalid_currency(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $this->expectException(DomainRuleViolation::class);
        $this->expectExceptionMessage('Currency must be a three-letter ISO code');
        $this->app->make(CheckoutService::class)->checkout($user, new CheckoutData([new OrderItemData($plan->id)], 'INVALID'));
    }
}
