<?php

namespace App\DTOs;

final readonly class CreateUserData
{
    public function __construct(
        public ?string $name = null,
        public ?string $username = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $locale = null,
        public ?string $timezone = null,
    ) {}

    public function toArray(): array
    {
        return array_filter(get_object_vars($this), static fn ($value) => $value !== null);
    }
}
