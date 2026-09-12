<?php

namespace App\Contracts;

use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Support\Collection;

interface CatalogService
{
    public function categories(bool $activeOnly = true): Collection;

    public function products(?Category $category = null, bool $activeOnly = true): Collection;

    public function plans(Product $product, bool $activeOnly = true): Collection;

    public function findPlan(int $planId): Plan;

    public function findPurchasablePlan(int $planId): Plan;
}
