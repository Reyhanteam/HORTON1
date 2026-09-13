<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\ConfiguresHortonQueue;
use App\Models\SupportMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;

final class SendSupportReplyJob implements ShouldQueue, ShouldBeUnique
{
    use ConfiguresHortonQueue;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor;
    public int $tries = 3;

    public function __construct(public readonly int $messageId)
    {
        $this->afterCommit = true;
        $this->configureHortonQueue((string) config('queue.horton.notifications_queue', config('queue.horton.queue', 'default')));
        $this->uniqueFor = $this->hortonQueueUniqueFor();
    }

    public function uniqueId(): string
    {
        return 'support-reply:'.$this->messageId;
    }

    public function middleware(): array
    {
        return $this->hortonQueueMiddleware();
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        $message = SupportMessage::query()->with('ticket.user.telegramAccount')->find($this->messageId);
        if ($message === null || $message->sender_type !== 'admin') return;

        $chatId = $message->ticket?->user?->telegramAccount?->telegram_user_id;
        if ($chatId === null) return;

        $attachments = is_array($message->attachments) ? $message->attachments : [];
        if ($attachments === []) {
            if (filled($message->message)) BOT::sendMessage($chatId, (string) $message->message);
            return;
        }

        $captionUsed = false;
        foreach ($attachments as $attachment) {
            $type = $attachment['type'] ?? null;
            $value = $attachment['file_id'] ?? $attachment['value'] ?? $attachment['url'] ?? null;
            if (!is_string($type) || !is_string($value) || $value === '') continue;

            $caption = ! $captionUsed && filled($message->message) ? (string) $message->message : null;
            match ($type) {
                'photo' => BOT::sendPhoto($chatId, $value, caption: $caption),
                'video' => BOT::sendVideo($chatId, $value, caption: $caption),
                'document', 'file' => BOT::sendDocument($chatId, $value, caption: $caption),
                default => BOT::sendMessage($chatId, (string) ($message->message ?? '')),
            };
            $captionUsed = $captionUsed || $caption !== null;
        }

        if (! $captionUsed && filled($message->message)) BOT::sendMessage($chatId, (string) $message->message);
    }
}
