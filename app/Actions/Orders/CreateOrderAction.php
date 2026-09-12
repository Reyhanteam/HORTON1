<?php

namespace App\Actions\Orders;

use App\Contracts\OrderService;
use App\Models\Order;
use App\Models\User;

final class CreateOrderAction
{
    public function __construct(private readonly OrderService $orders) {}

    public function execute(User $user, array $items, ?string $idempotencyKey = null): Order
    {
        return $this->orders->create($user, $items, $idempotencyKey);
    }
}
