<?php

namespace App\Actions\Support;

use App\Contracts\SupportService;
use App\DTOs\SupportTicketData;
use App\Models\SupportTicket;

final class CreateSupportTicketAction
{
    public function __construct(private readonly SupportService $support) {}
    public function execute(SupportTicketData $data): SupportTicket { return $this->support->createTicket($data); }
}
