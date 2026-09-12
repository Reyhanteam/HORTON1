<?php

namespace Tests\Feature;

use App\Contracts\CatalogService;
use App\Contracts\PricingService;
use App\DTOs\PriceQuote;
use App\Exceptions\DomainRuleViolation;
use App\Models\Category;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_returns_only_active_hierarchy_for_storefront(): void
    {
        $activeCategory = Category::factory()->create(['status' => 'active']);
        $inactiveCategory = Category::factory()->create(['status' => 'inactive']);
        $activeProduct = Product::factory()->for($activeCategory)->create(['status' => 'active']);
        $inactiveProduct = Product::factory()->for($activeCategory)->create(['status' => 'inactive']);
        $hiddenProduct = Product::factory()->for($inactiveCategory)->create(['status' => 'active']);
        Plan::factory()->for($activeProduct)->create(['status' => 'active']);
        Plan::factory()->for($activeProduct)->create(['status' => 'inactive']);

        $catalog = $this->app->make(CatalogService::class);

        $this->assertCount(1, $catalog->categories());
        $this->assertTrue($catalog->products($activeCategory)->contains($activeProduct));
        $this->assertFalse($catalog->products($activeCategory)->contains($inactiveProduct));
        $this->assertFalse($catalog->products($activeCategory)->contains($hiddenProduct));
        $this->assertCount(1, $catalog->plans($activeProduct));
    }

    public function test_purchasable_plan_requires_an_active_catalog_hierarchy_and_valid_duration(): void
    {
        $catalog = $this->app->make(CatalogService::class);
        $plan = Plan::factory()->create();

        $this->assertTrue($catalog->findPurchasablePlan($plan->id)->is($plan));

        $plan->product->update(['status' => 'inactive']);
        $this->expectException(DomainRuleViolation::class);
        $this->expectExceptionMessage('Product is not available');
        $catalog->findPurchasablePlan($plan->id);
    }

    public function test_pricing_selects_current_price_by_currency_and_validity(): void
    {
        Carbon::setTestNow('2026-09-12 12:00:00');
        $plan = Plan::factory()->create(['price' => 100000, 'currency' => 'IRR']);

        PlanPrice::factory()->for($plan)->create([
            'currency' => 'IRR', 'amount' => 150000, 'is_default' => true,
            'starts_at' => '2026-09-01 00:00:00', 'ends_at' => '2026-09-30 23:59:59',
        ]);
        PlanPrice::factory()->for($plan)->create([
            'currency' => 'IRR', 'amount' => 900000, 'is_default' => true,
            'starts_at' => '2026-10-01 00:00:00', 'ends_at' => null,
        ]);

        $quote = $this->app->make(PricingService::class)->quote($plan, 'IRR');

        $this->assertInstanceOf(PriceQuote::class, $quote);
        $this->assertSame(150000, $quote->amount);
        $this->assertSame('plan_price', $quote->source);
        Carbon::setTestNow();
    }

    public function test_pricing_falls_back_to_plan_price_only_for_plan_currency(): void
    {
        $plan = Plan::factory()->create(['price' => 220000, 'currency' => 'IRR']);
        $pricing = $this->app->make(PricingService::class);

        $this->assertSame(220000, $pricing->price($plan, 'irr'));
        $this->expectException(DomainRuleViolation::class);
        $pricing->price($plan, 'USD');
    }

    public function test_pricing_rejects_invalid_currency_and_invalid_price_window(): void
    {
        $plan = Plan::factory()->create();
        $pricing = $this->app->make(PricingService::class);

        $this->expectException(DomainRuleViolation::class);
        $pricing->price($plan, 'IR');

        PlanPrice::factory()->for($plan)->create([
            'currency' => 'IRR', 'amount' => 100000,
            'starts_at' => '2026-09-30 00:00:00', 'ends_at' => '2026-09-01 00:00:00',
        ]);
        Carbon::setTestNow('2026-09-12 12:00:00');
        $this->expectException(DomainRuleViolation::class);
        $pricing->price($plan, 'IRR');
        Carbon::setTestNow();
    }
}
