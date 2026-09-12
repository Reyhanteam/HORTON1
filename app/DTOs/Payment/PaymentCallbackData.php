<?php

namespace App\DTOs\Payment;

final readonly class PaymentCallbackData
{
    public function __construct(
        public string $callbackId,
        public ?string $status = null,
        public ?string $transactionId = null,
        public ?string $referenceId = null,
        public ?int $amount = null,
        public array $payload = [],
    ) {}
}
