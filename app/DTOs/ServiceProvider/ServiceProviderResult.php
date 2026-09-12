<?php

namespace App\DTOs\ServiceProvider;

use App\Enums\ServiceProviderOperation;
use Illuminate\Support\Carbon;

final readonly class ServiceProviderResult
{
    public function __construct(
        public ServiceProviderOperation $operation,
        public bool $successful,
        public ?string $externalId = null,
        public ?string $externalReference = null,
        public ?string $status = null,
        public ?Carbon $expiresAt = null,
        public ?int $capacity = null,
        public ?int $usedCapacity = null,
        public array $metadata = [],
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}

    public static function success(ServiceProviderOperation $operation, array $attributes = []): self
    {
        return new self(
            operation: $operation,
            successful: true,
            externalId: $attributes['externalId'] ?? null,
            externalReference: $attributes['externalReference'] ?? null,
            status: $attributes['status'] ?? null,
            expiresAt: $attributes['expiresAt'] ?? null,
            capacity: $attributes['capacity'] ?? null,
            usedCapacity: $attributes['usedCapacity'] ?? null,
            metadata: $attributes['metadata'] ?? [],
        );
    }

    public static function failure(ServiceProviderOperation $operation, string $code, string $message, array $attributes = []): self
    {
        return new self(
            operation: $operation,
            successful: false,
            externalId: $attributes['externalId'] ?? null,
            externalReference: $attributes['externalReference'] ?? null,
            status: $attributes['status'] ?? null,
            expiresAt: $attributes['expiresAt'] ?? null,
            capacity: $attributes['capacity'] ?? null,
            usedCapacity: $attributes['usedCapacity'] ?? null,
            metadata: $attributes['metadata'] ?? [],
            errorCode: $code,
            errorMessage: $message,
        );
    }
}
