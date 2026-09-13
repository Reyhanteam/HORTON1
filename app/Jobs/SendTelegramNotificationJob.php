<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\ConfiguresHortonQueue;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;
use Throwable;

final class SendTelegramNotificationJob implements ShouldQueue, ShouldBeUnique
{
    use ConfiguresHortonQueue;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $uniqueFor;

    public function __construct(public readonly int $notificationId)
    {
        $this->afterCommit = true;
        $this->configureHortonQueue((string) config('queue.horton.notifications_queue', config('queue.horton.queue', 'default')));
        $this->uniqueFor = $this->hortonQueueUniqueFor();
    }

    public function uniqueId(): string
    {
        return 'telegram-notification:'.$this->notificationId;
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return $this->hortonQueueMiddleware();
    }

    public function handle(): void
    {
        $notification = Notification::query()
            ->with(['user.telegramAccount'])
            ->findOrFail($this->notificationId);

        $delivery = $notification->deliveries()->firstOrCreate(
            ['channel' => 'telegram'],
            ['status' => 'pending', 'attempts' => 0],
        );

        if ($delivery->sent_at !== null) return;

        $telegramAccount = $notification->user?->telegramAccount;
        if (! $telegramAccount || ! $telegramAccount->is_active) {
            $this->failDelivery($delivery, 'Active Telegram account is not available.');
            return;
        }

        $delivery->increment('attempts');
        $delivery->forceFill([
            'status' => 'sending',
            'failed_at' => null,
            'error_message' => null,
        ])->save();

        $text = trim($notification->title."\n\n".$notification->body);
        $replyMarkup = data_get($notification->data, 'reply_markup');

        try {
            BOT::sendMessage(
                $telegramAccount->telegram_user_id,
                $text,
                replyMarkup: is_array($replyMarkup) ? $replyMarkup : null,
            );

            $delivery->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ])->save();
        } catch (Throwable $e) {
            $delivery->forceFill([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => mb_substr($e->getMessage(), 0, 2000),
            ])->save();
            throw $e;
        }
    }

    private function failDelivery($delivery, string $message): void
    {
        $delivery->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $message,
        ])->save();
    }
}
