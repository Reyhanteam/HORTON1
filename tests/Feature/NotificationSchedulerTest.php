<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Models\BotMessage;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderAccount;
use App\Models\TelegramAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;
use Tests\TestCase;

final class NotificationSchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiry_scheduler_sends_three_day_and_twenty_four_hour_notifications_once(): void
    {
        BOT::fake();
        $user = User::query()->create(['name' => 'Expiry Test', 'locale' => 'fa']);
        TelegramAccount::query()->create(['user_id' => $user->id, 'telegram_user_id' => 920001, 'is_active' => true]);
        $this->template('notifications.service.expiring_3_days');
        $this->template('notifications.service.expiring_24_hours');
        $this->template('notifications.service.expired');

        $plan = $this->plan();
        $provider = ServiceProvider::query()->create(['name' => 'Fake', 'slug' => 'fake-expiry', 'driver' => 'fake', 'status' => 'active']);
        $account = ServiceProviderAccount::query()->create(['service_provider_id' => $provider->id, 'name' => 'Fake Account', 'status' => 'active', 'priority' => 1]);

        $threeDay = $this->service($user, $plan, $provider, $account, Carbon::now()->addHours(48));
        $twentyFourHour = $this->service($user, $plan, $provider, $account, Carbon::now()->addHours(20));
        $expired = $this->service($user, $plan, $provider, $account, Carbon::now()->subMinute());

        Artisan::call('horton:notifications:dispatch');
        Artisan::call('horton:notifications:dispatch');

        self::assertSame(ServiceStatus::EXPIRED, $expired->refresh()->status);
        self::assertDatabaseHas('notifications', ['deduplication_key' => 'service:'.$threeDay->id.':service.expiring_3_days']);
        self::assertDatabaseHas('notifications', ['deduplication_key' => 'service:'.$twentyFourHour->id.':service.expiring_24_hours']);
        self::assertDatabaseHas('notifications', ['deduplication_key' => 'service:'.$expired->id.':expired']);
        self::assertSame(3, DB::table('notifications')->count());
    }

    private function service(User $user, Plan $plan, ServiceProvider $provider, ServiceProviderAccount $account, Carbon $expiresAt): Service
    {
        return Service::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'service_provider_id' => $provider->id,
            'provider_account_id' => $account->id,
            'status' => ServiceStatus::ACTIVE,
            'starts_at' => now()->subDay(),
            'expires_at' => $expiresAt,
            'capacity' => 10,
            'used_capacity' => 0,
            'is_trial' => false,
        ]);
    }

    private function plan(): Plan
    {
        $category = Category::query()->create(['name' => 'Expiry Category', 'slug' => 'expiry-category', 'status' => 'active']);
        $product = Product::query()->create(['category_id' => $category->id, 'name' => 'Expiry Product', 'slug' => 'expiry-product', 'status' => 'active']);
        return Plan::query()->create(['product_id' => $product->id, 'name' => 'Expiry Plan', 'slug' => 'expiry-plan', 'price' => 1000, 'currency' => 'IRR', 'duration_value' => 30, 'duration_unit' => 'day', 'capacity_value' => 10, 'capacity_unit' => 'GB', 'status' => 'active']);
    }

    private function template(string $prefix): void
    {
        BotMessage::query()->create(['key' => $prefix.'.title', 'locale' => 'fa', 'type' => 'message', 'text' => 'Title', 'is_active' => true]);
        BotMessage::query()->create(['key' => $prefix.'.body', 'locale' => 'fa', 'type' => 'message', 'text' => 'Body {expires_at}', 'is_active' => true]);
    }
}
