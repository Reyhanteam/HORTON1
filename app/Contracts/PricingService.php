<?php

namespace App\Contracts;

use App\DTOs\PriceQuote;
use App\Models\Plan;

interface PricingService
{
    public function price(Plan $plan, string $currency = 'IRR'): int;

    public function quote(Plan $plan, string $currency = 'IRR'): PriceQuote;
}
