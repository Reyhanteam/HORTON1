<?php

namespace App\Contracts;

use App\Models\Plan;

interface PricingService
{
    public function price(Plan $plan, string $currency = 'IRR'): int;
}
