<?php

namespace App\DTOs\Payment;

use Illuminate\Support\Carbon;

final readonly class PaymentResult
{
    public function __construct(
        public bool $successful,
        public string $status,
        public ?string $transactionId = null,
        public ?string $referenceId = null,
        public ?string $paymentUrl = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $metadata = [],
        public ?Carbon $verifiedAt = null,
    ) {}

    public static function pending(array $attributes = []): self
    {
        return new self(false, 'pending', $attributes['transactionId'] ?? null, $attributes['referenceId'] ?? null, $attributes['paymentUrl'] ?? null, metadata: $attributes['metadata'] ?? []);
    }

    public static function success(array $attributes = []): self
    {
        return new self(true, 'success', $attributes['transactionId'] ?? null, $attributes['referenceId'] ?? null, $attributes['paymentUrl'] ?? null, metadata: $attributes['metadata'] ?? [], verifiedAt: $attributes['verifiedAt'] ?? now());
    }

    public static function failed(string $code, string $message, array $attributes = []): self
    {
        return new self(false, 'failed', $attributes['transactionId'] ?? null, $attributes['referenceId'] ?? null, null, $code, $message, $attributes['metadata'] ?? []);
    }
}
