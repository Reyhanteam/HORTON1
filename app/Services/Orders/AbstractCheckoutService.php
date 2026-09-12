<?php

namespace App\Services\Orders;

use App\Contracts\CheckoutService;
use App\Contracts\InvoiceService;
use App\Contracts\OrderService;
use App\DTOs\CheckoutData;
use App\DTOs\CheckoutResult;
use App\DTOs\OrderItemData;
use App\Exceptions\DomainRuleViolation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

abstract class AbstractCheckoutService implements CheckoutService
{
    public function __construct(
        protected readonly OrderService $orders,
        protected readonly InvoiceService $invoices,
    ) {}

    final public function checkout(User $user, CheckoutData $data): CheckoutResult
    {
        $currency = strtoupper(trim($data->currency));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new DomainRuleViolation('Currency must be a three-letter ISO code.', 'checkout.invalid_currency');
        }
        if ($data->items === []) throw new DomainRuleViolation('Checkout must contain at least one item.', 'checkout.empty');

        return DB::transaction(function () use ($user, $data, $currency): CheckoutResult {
            $normalized = array_map(function (mixed $item): OrderItemData {
                if ($item instanceof OrderItemData) return $item;
                if (is_array($item)) return new OrderItemData(
                    planId: (int) ($item['planId'] ?? $item['plan_id'] ?? 0),
                    quantity: (int) ($item['quantity'] ?? 1),
                    unitPrice: array_key_exists('unitPrice', $item) ? (int) $item['unitPrice'] : (array_key_exists('unit_price', $item) ? (int) $item['unit_price'] : null),
                );
                throw new DomainRuleViolation('Invalid checkout item.', 'checkout.invalid_item');
            }, $data->items);

            $order = $this->orders->create($user, $normalized, $data->idempotencyKey, $currency);
            $order->forceFill(['metadata' => array_merge($order->metadata ?? [], $data->metadata)])->save();
            $invoice = $this->invoices->issue($order);
            return new CheckoutResult($order->refresh(), $invoice->refresh(), $order->wasRecentlyCreated);
        });
    }
}
