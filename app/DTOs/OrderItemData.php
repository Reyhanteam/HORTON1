<?php

namespace App\DTOs;

final readonly class OrderItemData
{
    public function __construct(
        public int $planId,
        public int $quantity = 1,
        public ?int $unitPrice = null,
    ) {}
}
