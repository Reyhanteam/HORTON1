<?php

namespace App\DTOs;

use Carbon\CarbonInterface;

final readonly class PriceQuote
{
    public function __construct(
        public int $planId,
        public string $currency,
        public int $amount,
        public string $source,
        public ?CarbonInterface $validFrom = null,
        public ?CarbonInterface $validUntil = null,
    ) {}
}
