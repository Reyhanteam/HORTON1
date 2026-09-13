<?php

declare(strict_types=1);

use App\Telegram\Controllers\ChannelMembershipController;
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

Route::middleware([EnsureChannelMembership::class])
    ->conversation(RegistrationConversation::name())
    ->step([RegistrationController::class, 'acceptance'])
    ->step([RegistrationController::class, 'phone'])
    ->ttl((int) config('telegram-bot-router.conversation.ttl', 3600))
    ->cancelOnCommand('cancel')
    ->register();

Route::middleware([EnsureChannelMembership::class])
    ->onCommand('start', [RegistrationController::class, 'start']);
