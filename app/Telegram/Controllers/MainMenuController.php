<?php

declare(strict_types=1);

namespace App\Telegram\Controllers;

use App\Contracts\BotMessageStore;
use App\Contracts\WalletService;
use App\Services\Registration\RegistrationService;
use App\Services\Telegram\BotMessageResponder;
use ReyhanTeam\TelegramBotRouter\Keyboard\Keyboard;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

final class MainMenuController
{
    public const RENEW = 'menu:renew';
    public const SHOP = 'menu:shop';
    public const TEST_ACCOUNT = 'menu:test-account';
    public const WALLET = 'menu:wallet';
    public const SERVICES = 'menu:services';
    public const PLANS = 'menu:plans';
    public const REFERRAL = 'menu:referral';
    public const TUTORIALS = 'menu:tutorials';
    public const SUPPORT = 'menu:support';

    public function __construct(
        private readonly RegistrationService $registration,
        private readonly BotMessageStore $messages,
        private readonly WalletService $wallets,
        private readonly BotMessageResponder $responder,
    ) {}

    public function show(TelegramUpdate $update): mixed
    {
        $user = $this->registration->userForUpdate($update);

        return $this->responder->respond(
            $update,
            $this->text($user),
            $this->keyboard(),
        );
    }

    public function action(TelegramUpdate $update): mixed
    {
        return $this->responder->respond(
            $update,
            $this->messages->get('menu.coming_soon', default: 'این بخش در حال آماده‌سازی است.', type: 'message') ?? 'این بخش در حال آماده‌سازی است.',
            Keyboard::inline()->callbackButton(
                $this->messages->get('menu.back_button', default: '↩️ بازگشت به منوی اصلی', type: 'button') ?? '↩️ بازگشت به منوی اصلی',
                'menu:home',
            )->toArray(),
        );
    }

    private function text(\App\Models\User $user): string
    {
        $account = $user->telegramAccount;
        $name = trim((string) ($user->name ?: trim(($account?->first_name ?? '') . ' ' . ($account?->last_name ?? ''))));
        $username = $account?->username ? '@' . ltrim((string) $account->username, '@') : 'ندارد';
        $phone = $user->phone ?: 'ثبت نشده';
        $balance = number_format($this->wallets->balance($user, 'IRR')) . ' ریال';

        return "سلام {$name} 🌷\n\n" .
            "👤 نام و نام خانوادگی: {$name}\n" .
            "🔹 نام کاربری: {$username}\n" .
            "📱 شماره تلفن: {$phone}\n" .
            "💰 موجودی کیف پول: {$balance}\n\n" .
            "یکی از گزینه‌های زیر را انتخاب کنید:";
    }

    private function keyboard(): array
    {
        $button = fn (string $key, string $fallback, string $callback): array => [
            'text' => $this->messages->get($key, default: $fallback, type: 'button') ?? $fallback,
            'callback_data' => $callback,
        ];

        return Keyboard::inline()
            ->callbackButton($button('menu.renew', '🔄 تمدید سرویس', self::RENEW)['text'], self::RENEW)->callbackButton($button('menu.shop', '🛒 خرید اشتراک', self::SHOP)['text'], self::SHOP)->row()
            ->callbackButton($button('menu.test_account', '🧪 اکانت تست', self::TEST_ACCOUNT)['text'], self::TEST_ACCOUNT)->callbackButton($button('menu.wallet', '💰 کیف پول + شارژ', self::WALLET)['text'], self::WALLET)->row()
            ->callbackButton($button('menu.services', '📦 سرویس‌های من', self::SERVICES)['text'], self::SERVICES)->callbackButton($button('menu.plans', '💳 تعرفه اشتراک‌ها', self::PLANS)['text'], self::PLANS)->row()
            ->callbackButton($button('menu.referral', '👥 زیرمجموعه‌گیری', self::REFERRAL)['text'], self::REFERRAL)->callbackButton($button('menu.tutorials', '🎓 آموزش', self::TUTORIALS)['text'], self::TUTORIALS)->row()
            ->callbackButton($button('menu.support', '🎧 پشتیبانی', self::SUPPORT)['text'], self::SUPPORT)
            ->toArray();
    }
}
