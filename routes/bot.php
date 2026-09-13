<?php

declare(strict_types=1);

use App\Telegram\Controllers\ChannelMembershipController;
use App\Telegram\Controllers\MainMenuController;
use App\Telegram\Controllers\RegistrationController;
use App\Telegram\Controllers\SupportController;
use App\Telegram\Conversations\RegistrationConversation;
use App\Telegram\Conversations\SupportConversation;
use App\Telegram\Middleware\EnsureChannelMembership;
use ReyhanTeam\TelegramBotRouter\Facades\Route;

$applyRateLimits = static function (mixed $route): mixed {
    if (!(bool) config('telegram-bot-router.rate_limit.enabled', false)) {
        return $route;
    }
    return $route->rateLimits((array) config('telegram-bot-router.rate_limit.limits', []));
};

$applyRateLimits(Route::onCallback(ChannelMembershipController::RECHECK, [ChannelMembershipController::class, 'recheck']));
$applyRateLimits(Route::onCallback('menu:home', [MainMenuController::class, 'show']));
$applyRateLimits(Route::onCallback(MainMenuController::RENEW, [MainMenuController::class, 'action']));
$applyRateLimits(Route::onCallback(MainMenuController::SHOP, [MainMenuController::class, 'action']));
$applyRateLimits(Route::onCallback(MainMenuController::TEST_ACCOUNT, [MainMenuController::class, 'action']));
$applyRateLimits(Route::onCallback(MainMenuController::WALLET, [MainMenuController::class, 'action']));
$applyRateLimits(Route::onCallback(MainMenuController::SERVICES, [MainMenuController::class, 'action']));
$applyRateLimits(Route::onCallback(MainMenuController::PLANS, [MainMenuController::class, 'action']));
$applyRateLimits(Route::onCallback(MainMenuController::REFERRAL, [MainMenuController::class, 'action']));
$applyRateLimits(Route::onCallback(MainMenuController::TUTORIALS, [MainMenuController::class, 'action']));
$applyRateLimits(Route::onCallback(MainMenuController::REPRESENTATIVE, [MainMenuController::class, 'action']));
$applyRateLimits(Route::onCallback(MainMenuController::SUPPORT, [SupportController::class, 'entry']));
$applyRateLimits(Route::onCallback(SupportController::FAQ, [SupportController::class, 'faq']));
$applyRateLimits(Route::onCallback(SupportController::FAQ_ACCEPTED, [SupportController::class, 'begin']));

Route::conversation(RegistrationConversation::name())
    ->step([RegistrationController::class, 'acceptance'])
    ->step([RegistrationController::class, 'phone'])
    ->ttl((int) config('telegram-bot-router.conversation.ttl', 3600))
    ->cacheStore(config('telegram-bot-router.conversation.cache_store'))
    ->cancelOnCommand('cancel')
    ->middleware([EnsureChannelMembership::class])
    ->register();

Route::conversation(SupportConversation::name())
    ->step([SupportController::class, 'department'])
    ->step([SupportController::class, 'sensitivity'])
    ->step([SupportController::class, 'message'])
    ->ttl((int) config('telegram-bot-router.conversation.ttl', 3600))
    ->cacheStore(config('telegram-bot-router.conversation.cache_store'))
    ->cancelOnCommand('cancel')
    ->middleware([EnsureChannelMembership::class])
    ->register();

$applyRateLimits(Route::middleware([EnsureChannelMembership::class])->onCommand('start', [RegistrationController::class, 'start']));
