<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SupportMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;

final class SendSupportReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $messageId)
    {
        $this->afterCommit = true;
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        $message = SupportMessage::query()->with('ticket.user.telegramAccount')->find($this->messageId);

        if ($message === null || $message->sender_type !== 'admin') {
            return;
        }

        $chatId = $message->ticket?->user?->telegramAccount?->telegram_user_id;

        if ($chatId === null) {
            return;
        }

        $attachments = $message->attachments ?? [];
        foreach ($attachments as $attachment) {
            $type = $attachment['type'] ?? null;
            $value = $attachment['file_id'] ?? $attachment['value'] ?? $attachment['url'] ?? null;
            if (!is_string($type) || !is_string($value) || $value === '') {
                continue;
            }

            $caption = $message->message ?: null;
            match ($type) {
                'photo' => BOT::sendPhoto($chatId, $value, caption: $caption),
                'video' => BOT::sendVideo($chatId, $value, caption: $caption),
                'document', 'file' => BOT::sendDocument($chatId, $value, caption: $caption),
                default => BOT::sendMessage($chatId, (string) ($message->message ?? '')),
            };
        }

        if ($attachments === [] && filled($message->message)) {
            BOT::sendMessage($chatId, (string) $message->message);
        }
    }
}
