<?php

namespace App\DTOs\ServiceProvider;

use App\Models\ServiceProviderAccount;

final readonly class ServiceProviderContext
{
    public function __construct(
        public ServiceProviderAccount $account,
        public array $configuration = [],
        public array $metadata = [],
    ) {}

    public static function fromAccount(ServiceProviderAccount $account): self
    {
        $provider = $account->provider()->firstOrFail();

        return new self(
            account: $account,
            configuration: $provider->configuration ?? [],
            metadata: $provider->metadata ?? [],
        );
    }
}
