<?php

namespace App\Services\Orders;

use App\Contracts\OrderStateMachine;
use App\Enums\OrderStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Order;

abstract class AbstractOrderStateMachine implements OrderStateMachine
{
    /** @var array<string, list<string>> */
    protected const TRANSITIONS = [
        'pending' => ['paid', 'cancelled', 'failed'],
        'paid' => ['processing', 'cancelled', 'failed'],
        'processing' => ['completed', 'failed'],
        'failed' => ['pending', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    final public function canTransition(OrderStatus $from, OrderStatus $to): bool
    {
        return in_array($to->value, static::TRANSITIONS[$from->value] ?? [], true);
    }

    final public function transition(Order $order, OrderStatus $to): Order
    {
        $from = $order->status instanceof OrderStatus ? $order->status : OrderStatus::from((string) $order->status);
        if ($from === $to) return $order->refresh();
        if (!$this->canTransition($from, $to)) {
            throw new DomainRuleViolation("Invalid order transition {$from->value} -> {$to->value}.", 'order.invalid_transition');
        }

        $attributes = ['status' => $to];
        if ($to === OrderStatus::PAID) $attributes['paid_at'] = $order->paid_at ?? now();
        if ($to === OrderStatus::CANCELLED) $attributes['cancelled_at'] = $order->cancelled_at ?? now();
        if ($to === OrderStatus::COMPLETED) $attributes['completed_at'] = $order->completed_at ?? now();
        return $order->forceFill($attributes)->save() ? $order->refresh() : $order->refresh();
    }
}
