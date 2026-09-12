<?php

namespace App\Services\Payment;

use App\DTOs\Payment\PaymentCallbackData;
use App\DTOs\Payment\PaymentResult;
use App\Models\Payment;
use Illuminate\Support\Str;

final class FakePaymentGateway extends AbstractPaymentGateway
{
    public function supports(string $currency): bool { return in_array(strtoupper($currency), ['IRR', 'USD', 'EUR'], true); }

    protected function performInitiate(Payment $payment): PaymentResult
    {
        if (($payment->metadata['simulate_failure'] ?? false) === true) return PaymentResult::failed('gateway.simulated_failure', 'Simulated gateway failure.');
        $reference = 'FAKE-' . Str::upper(Str::random(16));
        return PaymentResult::pending(['referenceId' => $reference, 'paymentUrl' => 'https://example.test/pay/'.$payment->uuid, 'metadata' => ['driver' => 'fake']]);
    }

    protected function performVerify(Payment $payment, ?PaymentCallbackData $callback): PaymentResult
    {
        if ($callback && strtolower((string) $callback->status) === 'failed') return PaymentResult::failed('gateway.declined', 'Payment was declined by the fake gateway.');
        if ($callback && strtolower((string) $callback->status) === 'success') return PaymentResult::success(['transactionId' => $callback->transactionId ?? 'FAKE-TXN-'.Str::upper(Str::random(12)), 'referenceId' => $callback->referenceId ?? $payment->reference_id, 'metadata' => ['driver' => 'fake']]);
        return PaymentResult::pending(['transactionId' => $payment->transaction_id, 'referenceId' => $payment->reference_id, 'metadata' => ['driver' => 'fake']]);
    }

    protected function performCallback(Payment $payment, PaymentCallbackData $callback): PaymentResult { return $this->performVerify($payment, $callback); }
}
