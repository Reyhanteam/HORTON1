<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\ConfiguresHortonQueue;
use App\Models\Broadcast;
use App\Models\BroadcastRecipient;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;
use Throwable;

final class SendBroadcastRecipientJob implements ShouldQueue, ShouldBeUnique
{
    use ConfiguresHortonQueue;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $uniqueFor;

    public function __construct(public readonly int $broadcastId, public readonly int $userId)
    {
        $this->afterCommit = true;
        $this->configureHortonQueue('broadcasts');
        $this->uniqueFor = $this->hortonQueueUniqueFor();
    }

    public function uniqueId(): string
    {
        return 'broadcast-recipient:'.$this->broadcastId.':'.$this->userId;
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return $this->hortonQueueMiddleware();
    }

    public function handle(): void
    {
        $broadcast = Broadcast::query()->findOrFail($this->broadcastId);
        if ($broadcast->status === 'cancelled') return;

        $recipient = BroadcastRecipient::query()
            ->where('broadcast_id', $this->broadcastId)
            ->where('user_id', $this->userId)
            ->first();
        if (! $recipient || $recipient->sent_at !== null) return;

        $user = User::query()->with('telegramAccount')->find($this->userId);
        if (! $user || ! $user->telegramAccount || ! $user->telegramAccount->is_active) {
            $this->finalFailure($recipient, 'Active Telegram account is not available.');
            return;
        }

        $recipient->increment('attempts');
        $recipient->forceFill(['status' => 'sending', 'failed_at' => null, 'error_message' => null])->save();

        try {
            $this->send($broadcast, $user);
            $recipient->forceFill(['status' => 'sent', 'sent_at' => now(), 'failed_at' => null])->save();
            Broadcast::query()->whereKey($broadcast->id)->increment('sent_count');
            $this->maybeComplete($broadcast->id);
        } catch (Throwable $e) {
            $recipient->forceFill(['status' => 'pending', 'error_message' => mb_substr($e->getMessage(), 0, 2000)])->save();
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $recipient = BroadcastRecipient::query()
            ->where('broadcast_id', $this->broadcastId)
            ->where('user_id', $this->userId)
            ->first();
        if (! $recipient || $recipient->sent_at !== null || $recipient->status === 'failed') return;

        $this->finalFailure($recipient, mb_substr($e->getMessage(), 0, 2000));
    }

    private function finalFailure(BroadcastRecipient $recipient, string $message): void
    {
        $recipient->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $message,
        ])->save();

        Broadcast::query()->whereKey($this->broadcastId)->increment('failed_count');
        $this->maybeComplete($this->broadcastId);
    }

    private function maybeComplete(int $broadcastId): void
    {
        DB::transaction(function () use ($broadcastId): void {
            $broadcast = Broadcast::query()->whereKey($broadcastId)->lockForUpdate()->first();
            if (! $broadcast || in_array($broadcast->status, ['cancelled', 'completed'], true)) return;
            if ((int) $broadcast->total_recipients <= 0) return;
            if ((int) $broadcast->sent_count + (int) $broadcast->failed_count < (int) $broadcast->total_recipients) return;
            $broadcast->forceFill(['status' => 'completed', 'completed_at' => now()])->save();
        });
    }

    private function send(Broadcast $broadcast, User $user): void
    {
        $chatId = $user->telegramAccount->telegram_user_id;
        $text = $this->render($broadcast->message, $user);
        $keyboard = is_array($broadcast->keyboard) ? $broadcast->keyboard : null;
        $media = is_array($broadcast->media) ? $broadcast->media : null;

        if ($media === null) {
            if ($text === null || trim($text) === '') throw new \RuntimeException('Broadcast message is empty.');
            BOT::sendMessage($chatId, $text, replyMarkup: $keyboard);
            return;
        }

        $type = (string) ($media['type'] ?? '');
        $value = (string) ($media['value'] ?? $media['file_id'] ?? $media['url'] ?? '');
        if ($type === '' || $value === '') throw new \RuntimeException('Broadcast media is invalid.');

        match ($type) {
            'photo' => BOT::sendPhoto($chatId, $value, caption: $text, replyMarkup: $keyboard),
            'document' => BOT::sendDocument($chatId, $value, caption: $text, replyMarkup: $keyboard),
            'video' => BOT::sendVideo($chatId, $value, caption: $text, replyMarkup: $keyboard),
            'animation' => BOT::sendAnimation($chatId, $value, caption: $text, replyMarkup: $keyboard),
            'audio' => BOT::sendAudio($chatId, $value, caption: $text, replyMarkup: $keyboard),
            'voice' => BOT::sendVoice($chatId, $value, caption: $text, replyMarkup: $keyboard),
            default => throw new \RuntimeException('Unsupported broadcast media type.'),
        };
    }

    private function render(?string $template, User $user): ?string
    {
        if ($template === null) return null;
        return strtr($template, [
            '{name}' => (string) ($user->name ?: $user->username ?: ''),
            '{username}' => (string) ($user->username ?: ''),
            '{phone}' => (string) ($user->phone ?: ''),
        ]);
    }
}
