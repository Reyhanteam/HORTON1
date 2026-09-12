<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayContract;
use App\DTOs\Payment\PaymentCallbackData;
use App\DTOs\Payment\PaymentResult;
use App\Exceptions\PaymentException;
use App\Models\Payment;

abstract class AbstractPaymentGateway implements PaymentGatewayContract
{
    final public function initiate(Payment $payment): PaymentResult
    {
        $this->assertPayment($payment);
        if ($payment->status->value === 'success') {
            return PaymentResult::success([
                'transactionId' => $payment->transaction_id,
                'referenceId' => $payment->reference_id,
                'verifiedAt' => $payment->verified_at,
            ]);
        }
        if (! $this->supports($payment->currency)) {
            throw new PaymentException('Gateway does not support this currency.', 'payment.currency_unsupported');
        }
        return $this->performInitiate($payment);
    }

    final public function verify(Payment $payment, ?PaymentCallbackData $callback = null): PaymentResult
    {
        $this->assertPayment($payment);
        if ($payment->status->value === 'success') {
            return PaymentResult::success(['transactionId' => $payment->transaction_id, 'referenceId' => $payment->reference_id, 'verifiedAt' => $payment->verified_at]);
        }
        return $this->performVerify($payment, $callback);
    }

    final public function callback(Payment $payment, PaymentCallbackData $callback): PaymentResult
    {
        $this->assertPayment($payment);
        if ($callback->amount !== null && $callback->amount !== (int) $payment->amount) {
            throw new PaymentException('Callback amount does not match payment amount.', 'payment.amount_mismatch');
        }
        return $this->performCallback($payment, $callback);
    }

    protected function assertPayment(Payment $payment): void
    {
        if ((int) $payment->amount <= 0) {
            throw new PaymentException('Payment amount must be greater than zero.', 'payment.invalid_amount');
        }
        if (blank($payment->currency)) {
            throw new PaymentException('Payment currency is required.', 'payment.currency_missing');
        }
    }

    abstract protected function performInitiate(Payment $payment): PaymentResult;
    abstract protected function performVerify(Payment $payment, ?PaymentCallbackData $callback): PaymentResult;
    abstract protected function performCallback(Payment $payment, PaymentCallbackData $callback): PaymentResult;
}
