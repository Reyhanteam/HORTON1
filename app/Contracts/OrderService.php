<?php

namespace App\Contracts;

use App\DTOs\OrderItemData;
use App\Models\Order;
use App\Models\User;

interface OrderService
{
    public function create(User $user, array $items, ?string $idempotencyKey = null): Order;
    public function recalculate(Order $order): Order;
    public function markPaid(Order $order): Order;
    public function cancel(Order $order): Order;
}
