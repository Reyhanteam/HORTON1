<?php

namespace App\Contracts;

use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;

interface CatalogService
{
    public function categories(bool $activeOnly = true);
    public function products(?Category $category = null, bool $activeOnly = true);
    public function plans(Product $product, bool $activeOnly = true);
    public function findPlan(int $planId): Plan;
}
