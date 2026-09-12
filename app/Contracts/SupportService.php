<?php

namespace App\Contracts;

use App\DTOs\SupportTicketData;
use App\Models\SupportTicket;

interface SupportService
{
    public function createTicket(SupportTicketData $data): SupportTicket;
    public function close(SupportTicket $ticket): SupportTicket;
}
