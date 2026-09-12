<?php

namespace App\Services\Catalog;

use App\Contracts\CatalogService;
use App\Exceptions\DomainRuleViolation;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;

abstract class AbstractCatalogService implements CatalogService
{
    final public function findPlan(int $planId): Plan
    {
        if ($planId <= 0) {
            throw new DomainRuleViolation('Plan id must be positive.', 'catalog.plan_id');
        }
        return $this->resolvePlan($planId);
    }

    protected function resolvePlan(int $planId): Plan
    {
        return Plan::query()->findOrFail($planId);
    }

    public function categories(bool $activeOnly = true)
    {
        $query = Category::query()->orderBy('sort_order');
        return $activeOnly ? $query->active()->get() : $query->get();
    }

    public function products(?Category $category = null, bool $activeOnly = true)
    {
        $query = Product::query()->orderBy('sort_order');
        if ($category) $query->where('category_id', $category->id);
        return $activeOnly ? $query->active()->get() : $query->get();
    }

    public function plans(Product $product, bool $activeOnly = true)
    {
        $query = $product->plans()->orderBy('sort_order');
        return $activeOnly ? $query->active()->get() : $query->get();
    }
}
