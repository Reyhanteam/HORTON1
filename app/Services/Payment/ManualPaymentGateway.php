<?php

namespace App\Services\Payment;

use App\DTOs\Payment\PaymentCallbackData;
use App\DTOs\Payment\PaymentResult;
use App\Models\Payment;

final class ManualPaymentGateway extends AbstractPaymentGateway
{
    public function supports(string $currency): bool { return strtoupper($currency) === 'IRR'; }

    protected function performInitiate(Payment $payment): PaymentResult
    {
        return PaymentResult::pending(['referenceId' => 'MANUAL-'.$payment->uuid, 'metadata' => ['driver' => 'manual', 'requires_admin_verification' => true]]);
    }

    protected function performVerify(Payment $payment, ?PaymentCallbackData $callback): PaymentResult
    {
        if ($callback && strtolower((string) $callback->status) === 'success') {
            return PaymentResult::success(['transactionId' => $callback->transactionId, 'referenceId' => $callback->referenceId ?? $payment->reference_id, 'metadata' => ['driver' => 'manual']]);
        }
        return PaymentResult::pending(['transactionId' => $payment->transaction_id, 'referenceId' => $payment->reference_id, 'metadata' => ['driver' => 'manual']]);
    }

    protected function performCallback(Payment $payment, PaymentCallbackData $callback): PaymentResult
    {
        return $this->performVerify($payment, $callback);
    }
}
