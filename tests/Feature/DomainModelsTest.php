<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Product;
use App\Models\User;
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
}
