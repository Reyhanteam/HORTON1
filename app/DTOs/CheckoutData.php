<?php

namespace App\DTOs;

final readonly class CheckoutData
{
    /** @param list<OrderItemData> $items */
    public function __construct(
        public array $items,
        public string $currency = 'IRR',
        public ?string $idempotencyKey = null,
        public array $metadata = [],
    ) {}
}
