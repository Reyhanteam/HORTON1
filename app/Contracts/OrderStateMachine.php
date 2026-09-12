<?php

namespace App\Contracts;

use App\Enums\OrderStatus;
use App\Models\Order;

interface OrderStateMachine
{
    public function canTransition(OrderStatus $from, OrderStatus $to): bool;

    public function transition(Order $order, OrderStatus $to): Order;
}
