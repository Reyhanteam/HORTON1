<?php

namespace App\DTOs;

final readonly class WalletMutationData
{
    public function __construct(
        public int $amount,
        public string $type,
        public ?string $description = null,
        public ?string $idempotencyKey = null,
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public array $metadata = [],
    ) {}
}
