<?php

namespace App\DTOs;

final readonly class WalletReconciliationResult
{
    public function __construct(
        public bool $consistent,
        public int $storedBalance,
        public int $calculatedBalance,
        public int $transactionCount,
        public ?int $firstInvalidTransactionId = null,
        public ?string $reason = null,
    ) {}
}
