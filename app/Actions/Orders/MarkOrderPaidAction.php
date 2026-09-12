<?php

namespace App\Actions\Orders;

use App\Contracts\OrderService;
use App\Models\Order;

final class MarkOrderPaidAction
{
    public function __construct(private readonly OrderService $orders) {}

    public function execute(Order $order): Order
    {
        return $this->orders->markPaid($order);
    }
}
