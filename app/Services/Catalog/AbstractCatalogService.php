<?php

namespace App\Services\Catalog;

use App\Contracts\CatalogService;
use App\Exceptions\DomainRuleViolation;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Support\Collection;

abstract class AbstractCatalogService implements CatalogService
{
    final public function findPlan(int $planId): Plan
    {
        if ($planId <= 0) {
            throw new DomainRuleViolation('Plan id must be positive.', 'catalog.plan_id');
        }

        return $this->resolvePlan($planId);
    }

    final public function findPurchasablePlan(int $planId): Plan
    {
        $plan = $this->findPlan($planId);

        if ($plan->status !== 'active') {
            throw new DomainRuleViolation('Plan is not available for purchase.', 'catalog.plan_inactive');
        }

        $product = $plan->relationLoaded('product') ? $plan->product : $plan->product()->first();
        if (!$product || $product->status !== 'active') {
            throw new DomainRuleViolation('Product is not available for purchase.', 'catalog.product_inactive');
        }

        $category = $product->relationLoaded('category') ? $product->category : $product->category()->first();
        if (!$category || $category->status !== 'active') {
            throw new DomainRuleViolation('Category is not available for purchase.', 'catalog.category_inactive');
        }

        if ($plan->duration_value <= 0 || !in_array($plan->duration_unit, ['hour', 'day', 'week', 'month', 'year'], true)) {
            throw new DomainRuleViolation('Plan duration is invalid.', 'catalog.duration');
        }

        if ($plan->price < 0) {
            throw new DomainRuleViolation('Plan price cannot be negative.', 'catalog.price');
        }

        return $plan;
    }

    protected function resolvePlan(int $planId): Plan
    {
        return Plan::query()->findOrFail($planId);
    }

    final public function categories(bool $activeOnly = true): Collection
    {
        $query = Category::query()
            ->withCount(['children', 'products'])
            ->orderBy('sort_order')
            ->orderBy('id');

        return $activeOnly ? $query->active()->get() : $query->get();
    }

    final public function products(?Category $category = null, bool $activeOnly = true): Collection
    {
        $query = Product::query()
            ->withCount('plans')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($category) {
            $query->where('category_id', $category->id);
        }

        if ($activeOnly) {
            $query->active()->whereHas('category', fn ($q) => $q->active());
        }

        return $query->get();
    }

    final public function plans(Product $product, bool $activeOnly = true): Collection
    {
        $query = $product->plans()
            ->with('prices')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }
}
