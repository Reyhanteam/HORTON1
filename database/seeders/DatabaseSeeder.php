<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BotMessage;
use App\Models\BotSetting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $messages = [
            ['key' => 'registration.rules', 'type' => 'message', 'text' => "سلام {name} 🌷\n\nبرای استفاده از ربات، ابتدا قوانین را مطالعه و تأیید کنید."],
            ['key' => 'registration.accept_button', 'type' => 'button', 'button_type' => 'inline', 'text' => '✅ قوانین را می‌پذیرم'],
            ['key' => 'registration.decline_button', 'type' => 'button', 'button_type' => 'inline', 'text' => '❌ انصراف'],
            ['key' => 'registration.invalid_acceptance', 'type' => 'message', 'text' => 'لطفاً یکی از گزینه‌های تأیید یا انصراف را انتخاب کنید.'],
            ['key' => 'registration.phone_prompt', 'type' => 'message', 'text' => 'لطفاً برای تکمیل ثبت‌نام، شماره تلفن خود را با دکمه زیر ارسال کنید.'],
            ['key' => 'registration.share_phone_button', 'type' => 'button', 'button_type' => 'reply', 'text' => '📱 ارسال شماره تلفن'],
            ['key' => 'registration.phone_required', 'type' => 'message', 'text' => 'ارسال شماره تلفن الزامی است.'],
            ['key' => 'registration.phone_invalid', 'type' => 'message', 'text' => 'شماره تلفن معتبر نیست. لطفاً دوباره با دکمه ارسال شماره تلفن تلاش کنید.'],
            ['key' => 'registration.phone_owner_mismatch', 'type' => 'message', 'text' => 'فقط شماره تلفن متعلق به حساب تلگرام خودتان قابل قبول است.'],
            ['key' => 'registration.phone_taken', 'type' => 'message', 'text' => 'این شماره تلفن قبلاً برای حساب دیگری ثبت شده است.'],
            ['key' => 'registration.success', 'type' => 'message', 'text' => 'ثبت‌نام با موفقیت انجام شد. خوش آمدید {name} 🎉'],
            ['key' => 'registration.cancelled', 'type' => 'message', 'text' => 'ثبت‌نام لغو شد. هر زمان خواستید با /start دوباره شروع کنید.'],
            ['key' => 'registration.already_active', 'type' => 'message', 'text' => 'حساب شما از قبل فعال است. خوش آمدید {name} 👋'],
            ['key' => 'registration.blocked', 'type' => 'message', 'text' => 'دسترسی این حساب مسدود شده است.'],
            ['key' => 'registration.not_started', 'type' => 'message', 'text' => 'فرآیند ثبت‌نام شروع نشده است. لطفاً /start را ارسال کنید.'],
            ['key' => 'registration.telegram_user_required', 'type' => 'message', 'text' => 'اطلاعات کاربر تلگرام یافت نشد.'],
            ['key' => 'membership.restricted', 'type' => 'message', 'text' => 'برای استفاده از ربات، ابتدا در کانال‌های زیر عضو شوید و سپس «بررسی عضویت» را بزنید.'],
            ['key' => 'membership.recheck', 'type' => 'button', 'button_type' => 'inline', 'text' => '🔄 بررسی عضویت'],
            ['key' => 'membership.verified', 'type' => 'message', 'text' => 'عضویت شما تأیید شد. اکنون می‌توانید از ربات استفاده کنید.'],
            ['key' => 'membership.unavailable', 'type' => 'message', 'text' => 'در حال حاضر بررسی عضویت کانال‌ها انجام نشد. لطفاً چند لحظه بعد دوباره تلاش کنید.'],
        ];

        foreach ($messages as $message) {
            BotMessage::query()->updateOrCreate(
                ['key' => $message['key'], 'locale' => 'fa'],
                [
                    'type' => $message['type'],
                    'button_type' => $message['button_type'] ?? null,
                    'text' => $message['text'],
                    'is_active' => true,
                ],
            );
        }

        BotSetting::query()->updateOrCreate(
            ['key' => 'features.phone_verification'],
            ['value' => 'true', 'type' => 'boolean', 'is_public' => false],
        );

        BotSetting::query()->updateOrCreate(
            ['key' => 'features.channel_membership'],
            ['value' => 'false', 'type' => 'boolean', 'is_public' => false],
        );
    }
}
