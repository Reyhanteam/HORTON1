<?php

namespace App\Services\Support;

use App\Contracts\SupportService;
use App\DTOs\SupportTicketData;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class DatabaseSupportService implements SupportService
{
    public function createTicket(SupportTicketData $data): SupportTicket
    {
        return DB::transaction(function () use ($data) {
            $ticket = SupportTicket::query()->create(['uuid'=>Str::uuid()->toString(),'user_id'=>$data->userId,'subject'=>$data->subject,'priority'=>$data->priority,'status'=>'open','last_message_at'=>now()]);
            SupportMessage::query()->create(['ticket_id'=>$ticket->id,'sender_type'=>'user','sender_id'=>$data->userId,'message'=>$data->message]);
            return $ticket->refresh();
        });
    }

    public function close(SupportTicket $ticket): SupportTicket
    {
        $ticket->forceFill(['status'=>'closed','closed_at'=>now()])->save();
        return $ticket->refresh();
    }
}
