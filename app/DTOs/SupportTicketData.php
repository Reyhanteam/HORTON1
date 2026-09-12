<?php

namespace App\DTOs;

final readonly class SupportTicketData
{
    public function __construct(
        public int $userId,
        public string $subject,
        public string $message,
        public string $priority = 'normal',
    ) {}
}
