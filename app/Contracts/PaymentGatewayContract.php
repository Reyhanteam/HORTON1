<?php

namespace App\Contracts;

use App\DTOs\Payment\PaymentCallbackData;
use App\DTOs\Payment\PaymentResult;
use App\Models\Payment;

interface PaymentGatewayContract
{
    public function initiate(Payment $payment): PaymentResult;
    public function verify(Payment $payment, ?PaymentCallbackData $callback = null): PaymentResult;
    public function callback(Payment $payment, PaymentCallbackData $callback): PaymentResult;
    public function supports(string $currency): bool;
}
