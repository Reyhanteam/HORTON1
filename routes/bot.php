<?php

declare(strict_types=1);

use App\Telegram\Controllers\ChannelMembershipController;
use App\Telegram\Controllers\MainMenuController;
use App\Telegram\Controllers\RegistrationController;
use App\Telegram\Conversations\RegistrationConversation;
use App\Telegram\Middleware\EnsureChannelMembership;
use ReyhanTeam\TelegramBotRouter\Facades\Route;

/*
|--------------------------------------------------------------------------
| Telegram Bot Routes
|--------------------------------------------------------------------------
|
| Telegram routes are user-facing only. Administration remains exclusively
| in the web dashboard.
|
| Route matching, parameters, constraints, middleware, rate limits and
| conversations are delegated to ReyhanTeam/laravel-telegram-bot-router.
| Business logic stays inside application services/actions.
|
*/

$applyRateLimits = static function (mixed $route): mixed {
    if (!(bool) config('telegram-bot-router.rate_limit.enabled', false)) {
        return $route;
    }

    return $route->rateLimits(
        (array) config('telegram-bot-router.rate_limit.limits', [])
    );
};

$applyRateLimits(Route::onCallback(
    ChannelMembershipController::RECHECK,
    [ChannelMembershipController::class, 'recheck'],
));

$applyRateLimits(Route::onCallback(
    'menu:home',
    [MainMenuController::class, 'show'],
));

$applyRateLimits(Route::onCallback(
    MainMenuController::RENEW,
    [MainMenuController::class, 'action'],
));

$applyRateLimits(Route::onCallback(
    MainMenuController::SHOP,
    [MainMenuController::class, 'action'],
));

$applyRateLimits(Route::onCallback(
    MainMenuController::TEST_ACCOUNT,
    [MainMenuController::class, 'action'],
));

$applyRateLimits(Route::onCallback(
    MainMenuController::WALLET,
    [MainMenuController::class, 'action'],
));

$applyRateLimits(Route::onCallback(
    MainMenuController::SERVICES,
    [MainMenuController::class, 'action'],
));

$applyRateLimits(Route::onCallback(
    MainMenuController::PLANS,
    [MainMenuController::class, 'action'],
));

$applyRateLimits(Route::onCallback(
    MainMenuController::REFERRAL,
    [MainMenuController::class, 'action'],
));

$applyRateLimits(Route::onCallback(
    MainMenuController::TUTORIALS,
    [MainMenuController::class, 'action'],
));

$applyRateLimits(Route::onCallback(
    MainMenuController::SUPPORT,
    [MainMenuController::class, 'action'],
));

$applyRateLimits(Route::onCallback(
    MainMenuController::REPRESENTATIVE,
    [MainMenuController::class, 'action'],
));

Route::conversation(RegistrationConversation::name())
    ->step([RegistrationController::class, 'acceptance'])
    ->step([RegistrationController::class, 'phone'])
    ->ttl((int) config('telegram-bot-router.conversation.ttl', 3600))
    ->cacheStore(config('telegram-bot-router.conversation.cache_store'))
    ->cancelOnCommand('cancel')
    ->middleware([EnsureChannelMembership::class])
    ->register();

$applyRateLimits(
    Route::middleware([EnsureChannelMembership::class])
        ->onCommand('start', [RegistrationController::class, 'start'])
);
