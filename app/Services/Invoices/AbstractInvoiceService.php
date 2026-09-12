<?php

namespace App\Services\Invoices;

use App\Contracts\InvoiceService;
use App\Exceptions\DomainRuleViolation;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Str;

abstract class AbstractInvoiceService implements InvoiceService
{
    final public function issue(Order $order): Invoice
    {
        if (!$order->exists) throw new DomainRuleViolation('Invoice requires a persisted order.', 'invoice.order_required');
        $existing = $order->invoice()->first();
        if ($existing) return $existing->refresh();
        return Invoice::query()->create([
            'order_id' => $order->id,
            'invoice_number' => $this->invoiceNumber(),
            'status' => 'issued',
            'subtotal' => (int) $order->subtotal,
            'discount_amount' => (int) $order->discount_amount,
            'total_amount' => (int) $order->total_amount,
            'currency' => strtoupper((string) $order->currency),
            'issued_at' => now(),
            'metadata' => ['order_uuid' => $order->uuid],
        ]);
    }

    final public function markPaid(Order $order): Invoice
    {
        $invoice = $this->issue($order);
        if ($invoice->status === 'paid') return $invoice->refresh();
        if ($invoice->status === 'cancelled') throw new DomainRuleViolation('Cancelled invoice cannot be paid.', 'invoice.cancelled');
        return $invoice->forceFill(['status' => 'paid', 'paid_at' => $invoice->paid_at ?? now()])->save() ? $invoice->refresh() : $invoice->refresh();
    }

    private function invoiceNumber(): string
    {
        do { $number = 'INV-'.now()->format('Ym').'-'.strtoupper(Str::random(10)); }
        while (Invoice::query()->where('invoice_number', $number)->exists());
        return $number;
    }
}
