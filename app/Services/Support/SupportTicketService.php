<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Enums\SupportTicketStatus;
use App\Models\SupportContent;
use App\Models\SupportDepartment;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SupportTicketService
{
    public function departments(): \Illuminate\Database\Eloquent\Collection
    {
        return SupportDepartment::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
    }

    public function faq(): \Illuminate\Database\Eloquent\Collection
    {
        return SupportContent::query()->where('type', 'faq')->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
    }

    public function createTicket(User $user, int $departmentId, string $sensitivity, ?string $message, array $attachments = []): SupportTicket
    {
        if (!in_array($sensitivity, ['low', 'normal', 'high'], true)) throw new InvalidArgumentException('Invalid support ticket sensitivity.');

        $department = SupportDepartment::query()->whereKey($departmentId)->where('is_active', true)->firstOrFail();
        if (blank($message) && $attachments === []) throw new InvalidArgumentException('Support ticket message is empty.');

        return DB::transaction(function () use ($user, $department, $sensitivity, $message, $attachments): SupportTicket {
            $ticket = SupportTicket::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'department_id' => $department->id,
                'subject' => $this->subject($message, $department->name),
                'status' => SupportTicketStatus::Open->value,
                'priority' => $sensitivity === 'high' ? 'high' : 'normal',
                'sensitivity' => $sensitivity,
                'last_message_at' => now(),
            ]);

            SupportMessage::query()->create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'user',
                'sender_id' => $user->id,
                'message' => $message,
                'attachments' => $attachments !== [] ? $attachments : null,
            ]);

            return $ticket->load(['department', 'messages']);
        });
    }

    public function addAdminReply(SupportTicket $ticket, int $adminId, ?string $message, array $attachments = []): SupportMessage
    {
        if (blank($message) && $attachments === []) throw new InvalidArgumentException('Support reply is empty.');

        return DB::transaction(function () use ($ticket, $adminId, $message, $attachments): SupportMessage {
            $reply = SupportMessage::query()->create([
                'ticket_id' => $ticket->id,
                'sender_type' => 'admin',
                'sender_id' => $adminId,
                'message' => $message,
                'attachments' => $attachments !== [] ? $attachments : null,
            ]);

            $ticket->forceFill([
                'status' => SupportTicketStatus::Answered->value,
                'last_message_at' => now(),
            ])->save();

            return $reply;
        });
    }

    public function setStatus(SupportTicket $ticket, string $status): SupportTicket
    {
        SupportTicketStatus::from($status);
        $ticket->forceFill([
            'status' => $status,
            'closed_at' => $status === SupportTicketStatus::Closed->value ? now() : null,
        ])->save();
        return $ticket->refresh();
    }

    private function subject(?string $message, string $department): string
    {
        $text = trim((string) $message);
        return mb_substr($text !== '' ? $text : $department, 0, 120);
    }
}
