<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\NotificationDispatcher;
use App\Enums\NotificationType;
use App\Models\BotMessage;
use App\Models\Broadcast;
use App\Models\TelegramAccount;
use App\Models\User;
use App\Services\Notifications\BroadcastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;

final class NotificationAndBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_thirty_notification_types_are_database_backed_and_delivered(): void
    {
        BOT::fake();
        $user = User::query()->create(['name' => 'Notification Test', 'locale' => 'fa']);
        TelegramAccount::query()->create(['user_id' => $user->id, 'telegram_user_id' => 900001, 'is_active' => true]);

        foreach (NotificationType::cases() as $type) {
            BotMessage::query()->create(['key' => 'notifications.'.$type->value.'.title', 'locale' => 'fa', 'type' => 'message', 'text' => 'عنوان '.$type->value, 'is_active' => true]);
            BotMessage::query()->create(['key' => 'notifications.'.$type->value.'.body', 'locale' => 'fa', 'type' => 'message', 'text' => 'بدنه {name} '.$type->value, 'is_active' => true]);
        }

        self::assertCount(30, NotificationType::cases());

        $dispatcher = app(NotificationDispatcher::class);
        foreach (NotificationType::cases() as $index => $type) {
            $dispatcher->dispatch($user, $type, ['amount' => 100 + $index, 'balance_after' => 500 + $index], 'test:'.$type->value);
        }

        self::assertDatabaseCount('notifications', 30);
        self::assertDatabaseCount('notification_deliveries', 30);
        self::assertSame(30, count(array_filter(BOT::getFacadeRoot()->calls(), static fn (array $call): bool => $call['method'] === 'sendMessage')));
    }

    public function test_notification_deduplication_is_idempotent(): void
    {
        BOT::fake();
        $user = User::query()->create(['name' => 'Dedup Test', 'locale' => 'fa']);
        TelegramAccount::query()->create(['user_id' => $user->id, 'telegram_user_id' => 900002, 'is_active' => true]);
        BotMessage::query()->create(['key' => 'notifications.order.created.title', 'locale' => 'fa', 'type' => 'message', 'text' => 'ثبت سفارش', 'is_active' => true]);
        BotMessage::query()->create(['key' => 'notifications.order.created.body', 'locale' => 'fa', 'type' => 'message', 'text' => 'سفارش {order_id}', 'is_active' => true]);

        $dispatcher = app(NotificationDispatcher::class);
        $dispatcher->dispatch($user, NotificationType::ORDER_CREATED, ['order_id' => 123], 'order:123:created');
        $dispatcher->dispatch($user, NotificationType::ORDER_CREATED, ['order_id' => 123], 'order:123:created');

        self::assertDatabaseCount('notifications', 1);
        self::assertDatabaseCount('notification_deliveries', 1);
        self::assertSame(1, count(array_filter(BOT::getFacadeRoot()->calls(), static fn (array $call): bool => $call['method'] === 'sendMessage')));
    }

    public function test_broadcast_is_batched_tracked_and_completed(): void
    {
        BOT::fake();
        $users = User::factory()->count(3)->create(['locale' => 'fa', 'status' => 'active']);
        foreach ($users as $index => $user) {
            TelegramAccount::query()->create(['user_id' => $user->id, 'telegram_user_id' => 910000 + $index, 'is_active' => true]);
        }

        $broadcast = Broadcast::query()->create([
            'name' => 'Test broadcast',
            'message' => 'سلام {name}',
            'targeting' => ['locale' => ['fa']],
            'batch_size' => 2,
            'status' => 'draft',
            'total_recipients' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
        ]);

        $result = app(BroadcastService::class)->queue($broadcast);

        self::assertSame('completed', $result->status);
        self::assertSame(3, $result->total_recipients);
        self::assertSame(3, $result->sent_count);
        self::assertSame(0, $result->failed_count);
        self::assertDatabaseCount('broadcast_recipients', 3);
        self::assertSame(3, count(array_filter(BOT::getFacadeRoot()->calls(), static fn (array $call): bool => $call['method'] === 'sendMessage')));
    }
}
