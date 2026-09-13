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
*/

Route::onCallback(ChannelMembershipController::RECHECK, [ChannelMembershipController::class, 'recheck']);
Route::onCallback('menu:home', [MainMenuController::class, 'show']);
Route::onCallback(MainMenuController::RENEW, [MainMenuController::class, 'action']);
Route::onCallback(MainMenuController::SHOP, [MainMenuController::class, 'action']);
Route::onCallback(MainMenuController::LUCK_WHEEL, [MainMenuController::class, 'action']);
Route::onCallback(MainMenuController::TEST_ACCOUNT, [MainMenuController::class, 'action']);
Route::onCallback(MainMenuController::WALLET, [MainMenuController::class, 'action']);
Route::onCallback(MainMenuController::SERVICES, [MainMenuController::class, 'action']);
Route::onCallback(MainMenuController::PLANS, [MainMenuController::class, 'action']);
Route::onCallback(MainMenuController::REFERRAL, [MainMenuController::class, 'action']);
Route::onCallback(MainMenuController::TUTORIALS, [MainMenuController::class, 'action']);
Route::onCallback(MainMenuController::SUPPORT, [MainMenuController::class, 'action']);
Route::onCallback(MainMenuController::REPRESENTATIVE, [MainMenuController::class, 'action']);

Route::conversation(RegistrationConversation::name())
    ->step([RegistrationController::class, 'acceptance'])
    ->step([RegistrationController::class, 'phone'])
    ->ttl((int) config('telegram-bot-router.conversation.ttl', 3600))
    ->cancelOnCommand('cancel')
    ->middleware([EnsureChannelMembership::class])
    ->register();

Route::middleware([EnsureChannelMembership::class])
    ->onCommand('start', [RegistrationController::class, 'start']);
