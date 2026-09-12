<?php

namespace App\Services\Catalog;

use App\Contracts\PricingService;
use App\Models\Plan;
use Illuminate\Support\Carbon;

abstract class AbstractPricingService implements PricingService
{
    final public function price(Plan $plan, string $currency = 'IRR'): int
    {
        $currency = strtoupper($currency);
        $now = Carbon::now();
        $price = $plan->prices()->where('currency',$currency)->where(function($q) use ($now){$q->whereNull('starts_at')->orWhere('starts_at','<=',$now);})->where(function($q) use ($now){$q->whereNull('ends_at')->orWhere('ends_at','>=',$now);})->orderByDesc('is_default')->orderByDesc('starts_at')->first();
        return $price ? (int)$price->amount : (int)$plan->price;
    }
}
