<?php

namespace App\DTOs;

use App\Models\Invoice;
use App\Models\Order;

final readonly class CheckoutResult
{
    public function __construct(
        public Order $order,
        public Invoice $invoice,
        public bool $created,
    ) {}
}
