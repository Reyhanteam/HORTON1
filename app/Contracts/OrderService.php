<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderService
{
    public function create(User $user, array $items, ?string $idempotencyKey = null, string $currency = 'IRR'): Order;
    public function recalculate(Order $order): Order;
    public function markPaid(Order $order): Order;
    public function cancel(Order $order): Order;
    public function transition(Order $order, \App\Enums\OrderStatus $status): Order;
    public function history(User $user, int $perPage = 15): LengthAwarePaginator;
}
