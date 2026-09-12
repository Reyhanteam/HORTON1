<?php

namespace App\Services\Payment;

use App\Actions\Orders\MarkOrderPaidAction;
use App\Contracts\PaymentGatewayContract;
use App\Contracts\WalletService;
use App\DTOs\Payment\PaymentCallbackData;
use App\DTOs\Payment\PaymentResult;
use App\DTOs\WalletMutationData;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

final class PaymentOrchestrator
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly WalletService $wallets,
        private readonly MarkOrderPaidAction $markOrderPaid,
        private readonly DatabaseManager $db,
    ) {}

    public function initiate(Order $order, PaymentMethod $method, ?string $gateway = null, ?string $idempotencyKey = null): Payment
    {
        if ((int) $order->total_amount <= 0) {
            throw new PaymentException('Order amount must be greater than zero.', 'payment.invalid_order_amount');
        }
        $key = $idempotencyKey ?? 'order-'.$order->id.'-'.$method->value;

        return $this->db->transaction(function () use ($order, $method, $gateway, $key): Payment {
            $existing = Payment::query()->where('idempotency_key', $key)->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }
            $payment = Payment::query()->create([
                'uuid' => (string) Str::uuid(),
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'method' => $method->value,
                'gateway' => $gateway ?? ($method === PaymentMethod::MANUAL ? 'manual' : ($method === PaymentMethod::ONLINE ? 'fake' : null)),
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'status' => PaymentStatus::PENDING,
                'idempotency_key' => $key,
                'metadata' => [],
            ]);
            return $this->processInitiation($payment);
        });
    }

    public function verify(Payment $payment, ?PaymentCallbackData $callback = null): Payment
    {
        $gateway = $this->gatewayFor($payment);
        $result = $gateway->verify($payment, $callback);
        return $this->applyResult($payment, $result);
    }

    public function handleCallback(Payment $payment, PaymentCallbackData $callback): Payment
    {
        $callbackModel = $payment->callbacks()->firstOrCreate(
            ['callback_id' => $callback->callbackId],
            ['gateway' => $payment->gateway, 'status' => 'received', 'payload' => $callback->payload]
        );
        if ($callbackModel->processed_at) {
            return $payment->refresh();
        }
        $result = $this->gatewayFor($payment)->callback($payment, $callback);
        $updated = $this->applyResult($payment, $result);
        $callbackModel->forceFill(['status' => $result->status, 'processed_at' => now()])->save();
        return $updated;
    }

    public function payWithWallet(Order $order, ?string $idempotencyKey = null): Payment
    {
        $key = $idempotencyKey ?? 'wallet-order-'.$order->id;
        return $this->db->transaction(function () use ($order, $key): Payment {
            $existing = Payment::query()->where('idempotency_key', $key)->lockForUpdate()->first();
            if ($existing) return $existing;
            $payment = Payment::query()->create([
                'uuid' => (string) Str::uuid(), 'order_id' => $order->id, 'user_id' => $order->user_id,
                'method' => PaymentMethod::WALLET->value, 'gateway' => null, 'amount' => $order->total_amount,
                'currency' => $order->currency, 'status' => PaymentStatus::PENDING, 'idempotency_key' => $key,
            ]);
            try {
                $this->wallets->debit($order->user, new WalletMutationData(
                    amount: $order->total_amount, type: 'order_payment', description: 'Wallet payment for order #'.$order->id,
                    idempotencyKey: 'payment-'.$payment->id, referenceType: Payment::class, referenceId: $payment->id,
                ), $order->currency);
            } catch (\Throwable $e) {
                $payment->update(['status' => PaymentStatus::FAILED, 'metadata' => ['error' => $e->getMessage()]]);
                throw $e;
            }
            return $this->applyResult($payment, PaymentResult::success(['transactionId' => 'WALLET-'.$payment->id, 'referenceId' => 'WALLET-'.$order->id]));
        });
    }

    private function processInitiation(Payment $payment): Payment
    {
        $attempt = $payment->attempts()->create(['gateway' => $payment->gateway, 'status' => 'pending', 'amount' => $payment->amount]);
        try {
            $result = $this->gatewayFor($payment)->initiate($payment);
            $attempt->update(['status' => $result->status, 'gateway_reference' => $result->referenceId, 'response_code' => $result->errorCode, 'failure_reason' => $result->errorMessage, 'metadata' => $result->metadata]);
            return $this->applyResult($payment, $result);
        } catch (\Throwable $e) {
            $attempt->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
            $payment->update(['status' => PaymentStatus::FAILED, 'metadata' => ['error_code' => $e instanceof PaymentException ? $e->errorCode : 'payment.gateway_error']]);
            throw $e;
        }
    }

    private function applyResult(Payment $payment, PaymentResult $result): Payment
    {
        $status = PaymentStatus::from($result->status);
        $payment->forceFill([
            'status' => $status,
            'transaction_id' => $result->transactionId ?? $payment->transaction_id,
            'reference_id' => $result->referenceId ?? $payment->reference_id,
            'verified_at' => $result->successful ? ($result->verifiedAt ?? now()) : $payment->verified_at,
            'paid_at' => $result->successful ? ($payment->paid_at ?? now()) : $payment->paid_at,
            'metadata' => array_merge($payment->metadata ?? [], $result->metadata),
        ])->save();
        if ($result->successful) {
            $this->markOrderPaid->execute($payment->order()->lockForUpdate()->firstOrFail());
        }
        return $payment->refresh();
    }

    private function gatewayFor(Payment $payment): PaymentGatewayContract
    {
        return $this->gateways->driver($payment->gateway);
    }
}
