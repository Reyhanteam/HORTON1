<?php

namespace App\Services\Orders;

use App\Contracts\CatalogService;
use App\Contracts\InvoiceService;
use App\Contracts\OrderService;
use App\Contracts\OrderStateMachine;
use App\Contracts\PricingService;
use App\DTOs\OrderItemData;
use App\Enums\OrderStatus;
use App\Events\OrderPaid;
use App\Exceptions\DomainRuleViolation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class AbstractOrderService implements OrderService
{
    public function __construct(
        protected readonly CatalogService $catalog,
        protected readonly PricingService $pricing,
        protected readonly OrderStateMachine $states,
        protected readonly InvoiceService $invoices,
    ) {}

    /** @param list<OrderItemData|array<string,mixed>> $items */
    final public function create(User $user, array $items, ?string $idempotencyKey = null, string $currency = 'IRR'): Order
    {
        $currency = strtoupper(trim($currency));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) throw new DomainRuleViolation('Currency must be a three-letter ISO code.', 'order.invalid_currency');
        if ($items === []) throw new DomainRuleViolation('Order must contain at least one item.', 'order.empty');
        if ($idempotencyKey !== null && trim($idempotencyKey) === '') throw new DomainRuleViolation('Idempotency key cannot be empty.', 'order.invalid_idempotency_key');

        return DB::transaction(function () use ($user, $items, $idempotencyKey, $currency): Order {
            if ($idempotencyKey !== null) {
                $existing = Order::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
                if ($existing) {
                    if ((int) $existing->user_id !== (int) $user->id || $existing->currency !== $currency) throw new DomainRuleViolation('Idempotency key is already bound to another order.', 'order.idempotency_conflict');
                    return $existing->load(['items', 'invoice']);
                }
            }

            $order = Order::query()->create([
                'uuid' => (string) Str::uuid(), 'user_id' => $user->id,
                'status' => OrderStatus::PENDING, 'currency' => $currency,
                'idempotency_key' => $idempotencyKey, 'metadata' => [],
            ]);
            foreach ($items as $item) $this->addItem($order, $item, $currency);
            return $this->recalculate($order);
        });
    }

    final public function recalculate(Order $order): Order
    {
        $order->loadMissing('items');
        $subtotal = (int) $order->items->sum('total_amount');
        $discount = max(0, (int) $order->discount_amount);
        $wallet = max(0, (int) $order->wallet_amount);
        $cashback = max(0, (int) $order->cashback_amount);
        if ($discount + $wallet + $cashback > $subtotal) throw new DomainRuleViolation('Order adjustments cannot exceed subtotal.', 'order.adjustment_exceeds_subtotal');
        $total = $subtotal - $discount - $wallet - $cashback;
        return $order->forceFill(['subtotal' => $subtotal, 'total_amount' => $total])->save() ? $order->refresh() : $order->refresh();
    }

    final public function markPaid(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === OrderStatus::CANCELLED) throw new DomainRuleViolation('Cancelled orders cannot be paid.', 'order.invalid_payment_state');
            if (!in_array($locked->status, [OrderStatus::PAID, OrderStatus::PROCESSING, OrderStatus::COMPLETED], true)) $this->states->transition($locked, OrderStatus::PAID);
            $this->invoices->markPaid($locked->refresh());
            $updated = $locked->refresh();
            if ($updated->status === OrderStatus::PAID) OrderPaid::dispatch($updated);
            return $updated;
        });
    }

    final public function cancel(Order $order): Order
    {
        $order = $order->refresh();
        if (in_array($order->status, [OrderStatus::PAID, OrderStatus::PROCESSING, OrderStatus::COMPLETED], true)) throw new DomainRuleViolation('Paid or processing orders require a refund/compensation flow instead of cancellation.', 'order.cancel_paid');
        return $this->transition($order, OrderStatus::CANCELLED);
    }

    final public function transition(Order $order, OrderStatus $status): Order
    {
        return DB::transaction(fn (): Order => $this->states->transition(Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail(), $status));
    }

    final public function history(User $user, int $perPage = 15): LengthAwarePaginator
    {
        if ($perPage < 1 || $perPage > 100) throw new DomainRuleViolation('Pagination size must be between 1 and 100.', 'order.invalid_pagination');
        return Order::query()->where('user_id', $user->id)->with(['items', 'invoice', 'payments'])->latest('id')->paginate($perPage);
    }

    private function addItem(Order $order, OrderItemData|array $item, string $currency): void
    {
        $data = $item instanceof OrderItemData ? $item : new OrderItemData(
            planId: (int) ($item['plan_id'] ?? $item['planId'] ?? 0),
            quantity: (int) ($item['quantity'] ?? 1),
            unitPrice: array_key_exists('unit_price', $item) ? (int) $item['unit_price'] : (array_key_exists('unitPrice', $item) ? (int) $item['unitPrice'] : null),
        );
        if ($data->quantity <= 0) throw new DomainRuleViolation('Item quantity must be positive.', 'order.quantity');
        $plan = $this->catalog->findPurchasablePlan($data->planId);
        $quote = $this->pricing->quote($plan, $currency);
        if ($data->unitPrice !== null && $data->unitPrice !== $quote->amount) throw new DomainRuleViolation('Client price does not match the current catalog price.', 'order.price_mismatch');
        $product = $plan->product;
        OrderItem::query()->create([
            'order_id' => $order->id, 'product_id' => $plan->product_id, 'plan_id' => $plan->id,
            'product_name_snapshot' => $product?->name, 'plan_name_snapshot' => $plan->name,
            'duration_value_snapshot' => $plan->duration_value, 'duration_unit_snapshot' => $plan->duration_unit,
            'capacity_value_snapshot' => $plan->capacity_value, 'capacity_unit_snapshot' => $plan->capacity_unit,
            'is_trial_snapshot' => (bool) $plan->is_trial,
            'name' => $plan->name, 'quantity' => $data->quantity, 'unit_price' => $quote->amount,
            'discount_amount' => 0, 'total_amount' => $quote->amount * $data->quantity,
            'metadata' => ['price_source' => $quote->source, 'quoted_at' => now()->toIso8601String()],
        ]);
    }
}
