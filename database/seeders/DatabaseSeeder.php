<?php

declare(strict_types=1);

namespace Database\Seeders;

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
            'registration.rules' => "سلام {name} 🌷\n\nبرای استفاده از ربات، ابتدا قوانین را مطالعه و تأیید کنید.",
            'registration.accept_button' => '✅ قوانین را می‌پذیرم',
            'registration.decline_button' => '❌ انصراف',
            'registration.invalid_acceptance' => 'لطفاً یکی از گزینه‌های تأیید یا انصراف را انتخاب کنید.',
            'registration.phone_prompt' => 'لطفاً برای تکمیل ثبت‌نام، شماره تلفن خود را با دکمه زیر ارسال کنید.',
            'registration.share_phone_button' => '📱 ارسال شماره تلفن',
            'registration.phone_required' => 'ارسال شماره تلفن الزامی است.',
            'registration.phone_invalid' => 'شماره تلفن معتبر نیست. لطفاً دوباره با دکمه ارسال شماره تلفن تلاش کنید.',
            'registration.phone_owner_mismatch' => 'فقط شماره تلفن متعلق به حساب تلگرام خودتان قابل قبول است.',
            'registration.phone_taken' => 'این شماره تلفن قبلاً برای حساب دیگری ثبت شده است.',
            'registration.success' => 'ثبت‌نام با موفقیت انجام شد. خوش آمدید {name} 🎉',
            'registration.cancelled' => 'ثبت‌نام لغو شد. هر زمان خواستید با /start دوباره شروع کنید.',
            'registration.already_active' => 'حساب شما از قبل فعال است. خوش آمدید {name} 👋',
            'registration.blocked' => 'دسترسی این حساب مسدود شده است.',
            'registration.not_started' => 'فرآیند ثبت‌نام شروع نشده است. لطفاً /start را ارسال کنید.',
            'registration.telegram_user_required' => 'اطلاعات کاربر تلگرام یافت نشد.',
            'membership.restricted' => 'برای استفاده از ربات، ابتدا در کانال‌های زیر عضو شوید و سپس «بررسی عضویت» را بزنید.',
            'membership.recheck' => '🔄 بررسی عضویت',
            'membership.verified' => 'عضویت شما تأیید شد. اکنون می‌توانید از ربات استفاده کنید.',
            'membership.unavailable' => 'در حال حاضر بررسی عضویت کانال‌ها انجام نشد. لطفاً چند لحظه بعد دوباره تلاش کنید.',
        ];

        foreach ($messages as $key => $value) {
            BotSetting::query()->updateOrCreate(
                ['key' => 'messages.' . $key],
                ['value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'type' => 'string', 'is_public' => true],
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
