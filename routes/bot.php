<?php

declare(strict_types=1);

use App\Services\Registration\RegistrationService;
use App\Telegram\Controllers\RegistrationController;
use App\Telegram\Conversations\RegistrationConversation;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;
use ReyhanTeam\TelegramBotRouter\Facades\Route;
use ReyhanTeam\TelegramBotRouter\Conversation\ConversationManager;

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

// Explicit callback routes ensure the registration conversation receives its
// acceptance decisions through the package router.
Route::onCallbackQuery(RegistrationService::ACCEPT, function ($update) {
    return app(ConversationManager::class)->handle($update);
});

Route::onCallbackQuery(RegistrationService::DECLINE, function ($update) {
    return app(ConversationManager::class)->handle($update);
});
