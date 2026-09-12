<?php

namespace App\DTOs;

final readonly class NotificationData
{
    public function __construct(
        public int $userId,
        public string $type,
        public string $title,
        public ?string $body = null,
        public array $data = [],
    ) {}
}
