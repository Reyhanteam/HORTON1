<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Facades\DB;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

final class BotMessageResponder
{
    public function respond(TelegramUpdate $update, string $text, ?array $replyMarkup = null): mixed
    {
        $chatId = $update->chatId();

        if ($this->isStart($update)) {
            return $this->sendNew($chatId, $text, $replyMarkup);
        }

        $messageId = $this->messageIdForUpdate($update);

        if ($messageId === null && $chatId !== null) {
            $stored = DB::table('telegram_bot_message_states')
                ->where('chat_id', (string) $chatId)
                ->value('message_id');

            $messageId = $stored !== null ? (int) $stored : null;
        }

        if ($messageId !== null && $chatId !== null) {
            $result = BOT::editMessageText(
                $chatId,
                $messageId,
                $text,
                replyMarkup: $replyMarkup,
            );

            $this->remember($chatId, $messageId);

            return $result;
        }

        return $this->sendNew($chatId, $text, $replyMarkup);
    }

    public function sendNew(int|string|null $chatId, string $text, ?array $replyMarkup = null): mixed
    {
        if ($chatId === null) {
            return null;
        }

        $result = BOT::sendMessage($chatId, $text, replyMarkup: $replyMarkup);
        $messageId = data_get($result, 'message_id');

        if ($messageId !== null) {
            $this->remember($chatId, (int) $messageId);
        }

        return $result;
    }

    private function messageIdForUpdate(TelegramUpdate $update): ?int
    {
        $callbackMessageId = data_get(
            $update->originalUpdate(),
            'callback_query.message.message_id',
        );

        return $callbackMessageId !== null ? (int) $callbackMessageId : null;
    }

    private function isStart(TelegramUpdate $update): bool
    {
        $text = $update->message?->text;

        if (!is_string($text)) {
            return false;
        }

        $command = trim($text);

        return $command === '/start' || str_starts_with($command, '/start@');
    }

    private function remember(int|string $chatId, int $messageId): void
    {
        DB::table('telegram_bot_message_states')->updateOrInsert(
            ['chat_id' => (string) $chatId],
            [
                'message_id' => $messageId,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}
