<?php

declare(strict_types=1);

namespace App\Telegram\Controllers;

use App\Contracts\BotMessageStore;
use App\Contracts\WalletService;
use App\Services\Registration\RegistrationService;
use App\Services\Telegram\BotMessageResponder;
use App\Models\User;
use LogicException;
use ReyhanTeam\TelegramBotRouter\Keyboard\Keyboard;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

final class MainMenuController
{
    public const RENEW = 'menu:renew';
    public const SHOP = 'menu:shop';
    public const LUCK_WHEEL = 'menu:luck-wheel';
    public const TEST_ACCOUNT = 'menu:test-account';
    public const WALLET = 'menu:wallet';
    public const SERVICES = 'menu:services';
    public const PLANS = 'menu:plans';
    public const REFERRAL = 'menu:referral';
    public const TUTORIALS = 'menu:tutorials';
    public const SUPPORT = 'menu:support';
    public const REPRESENTATIVE = 'menu:representative';

    public function __construct(
        private readonly RegistrationService $registration,
        private readonly BotMessageStore $messages,
        private readonly WalletService $wallets,
        private readonly BotMessageResponder $responder,
    ) {}

    public function show(TelegramUpdate $update): mixed
    {
        $user = $this->registration->userForUpdate($update);

        return $this->responder->respond($update, $this->text($user), $this->keyboard());
    }

    public function action(TelegramUpdate $update): mixed
    {
        return $this->responder->respond(
            $update,
            $this->message('menu.coming_soon'),
            Keyboard::inline()->callbackButton(
                $this->button('menu.back_button'),
                'menu:home',
            )->toArray(),
        );
    }

    private function text(User $user): string
    {
        $account = $user->telegramAccount;
        $name = trim((string) ($user->name ?: trim(($account?->first_name ?? '') . ' ' . ($account?->last_name ?? ''))));
        $username = $account?->username ? '@' . ltrim((string) $account->username, '@') : $this->message('menu.no_username');
        $phone = $user->phone ?: $this->message('menu.phone_not_registered');
        $balance = number_format($this->wallets->balance($user, 'IRR')) . ' ' . $this->message('menu.currency_irr');

        return $this->render('menu.home', [
            '{name}' => $name,
            '{username}' => $username,
            '{phone}' => $phone,
            '{balance}' => $balance,
        ]);
    }

    private function keyboard(): array
    {
        return Keyboard::inline()
            ->callbackButton($this->button('menu.renew'), self::RENEW)
            ->callbackButton($this->button('menu.shop'), self::SHOP)
            ->row()
            ->callbackButton($this->button('menu.luck_wheel'), self::LUCK_WHEEL)
            ->callbackButton($this->button('menu.test_account'), self::TEST_ACCOUNT)
            ->row()
            ->callbackButton($this->button('menu.wallet'), self::WALLET)
            ->callbackButton($this->button('menu.services'), self::SERVICES)
            ->row()
            ->callbackButton($this->button('menu.plans'), self::PLANS)
            ->callbackButton($this->button('menu.referral'), self::REFERRAL)
            ->row()
            ->callbackButton($this->button('menu.tutorials'), self::TUTORIALS)
            ->callbackButton($this->button('menu.support'), self::SUPPORT)
            ->row()
            ->callbackButton($this->button('menu.representative'), self::REPRESENTATIVE)
            ->toArray();
    }

    private function message(string $key): string
    {
        $message = $this->messages->get($key, type: 'message');

        if ($message === null) {
            throw new LogicException("Missing bot message: {$key}");
        }

        return $message;
    }

    private function button(string $key): string
    {
        $button = $this->messages->get($key, type: 'button');

        if ($button === null) {
            throw new LogicException("Missing bot button message: {$key}");
        }

        return $button;
    }

    private function render(string $key, array $replace = []): string
    {
        return strtr($this->message($key), array_map(static fn ($value): string => (string) $value, $replace));
    }
}
