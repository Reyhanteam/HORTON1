<?php

declare(strict_types=1);

use App\Telegram\Controllers\RegistrationController;
use App\Telegram\Conversations\RegistrationConversation;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;
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

BOT::conversation(RegistrationConversation::name())
    ->step([RegistrationController::class, 'acceptance'])
    ->step([RegistrationController::class, 'phone'])
    ->ttl((int) config('telegram-bot-router.conversation.ttl', 3600))
    ->cancelOnCommand('cancel')
    ->register();

Route::onCommand('start', [RegistrationController::class, 'start']);
