<?php

declare(strict_types=1);

namespace App\Telegram\Controllers;

use App\Services\Registration\RegistrationService;
use App\Services\Telegram\BotMessageResponder;
use App\Telegram\Conversations\RegistrationConversation;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use ReyhanTeam\TelegramBotRouter\Conversation\ConversationManager;
use ReyhanTeam\TelegramBotRouter\Keyboard\Keyboard;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

final class RegistrationController
{
    public function __construct(
        private readonly RegistrationService $registration,
        private readonly ConversationManager $conversations,
        private readonly BotMessageResponder $messages,
        private readonly MainMenuController $mainMenu,
    ) {}

    public function start(TelegramUpdate $update): mixed
    {
        $user = $this->registration->begin($update);

        if ($user->isActive()) {
            return $this->mainMenu->show($update);
        }

        $this->conversations->start(
            $update,
            RegistrationConversation::name(),
            RegistrationConversation::steps(),
            (int) config('telegram-bot-router.conversation.ttl', 3600),
        );

        return $this->messages->sendNew(
            $update->chatId(),
            $this->registration->render('registration.rules', ['{name}' => $user->name ?? '']),
            Keyboard::inline()
                ->callbackButton($this->registration->button('registration.accept_button'), RegistrationService::ACCEPT)
                ->row()
                ->callbackButton($this->registration->button('registration.decline_button'), RegistrationService::DECLINE)
                ->toArray(),
        );
    }

    public function acceptance(TelegramUpdate $update, mixed $input): array
    {
        // A conversation may outlive a TelegramAccount row (for example after
        // a database reset). Reconcile the account before processing the step
        // instead of failing with registration.not_started.
        $user = $this->registration->begin($update);
        $value = $input->required()->value('');

        if ($value === RegistrationService::DECLINE) {
            $this->send($update, $this->registration->message('registration.cancelled'));
            return ['done' => true, 'data' => ['declined' => true]];
        }

        if ($value !== RegistrationService::ACCEPT) {
            $this->send($update, $this->registration->message('registration.invalid_acceptance'));
            throw new InvalidArgumentException('Invalid registration acceptance value.');
        }

        $result = $this->registration->accept($user);

        if ($result['done'] === true) {
            $this->mainMenu->show($update);
            return ['done' => true, 'data' => ['accepted' => true, 'phone_verified' => false]];
        }

        $this->messages->sendNew(
            $update->chatId(),
            $this->registration->message('registration.phone_prompt'),
            [
                'keyboard' => [[[
                    'text' => $this->registration->button('registration.share_phone_button'),
                    'request_contact' => true,
                ]]],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ],
        );

        return ['done' => false, 'data' => ['accepted' => true]];
    }

    public function phone(TelegramUpdate $update): array
    {
        // Keep registration idempotent so a stale conversation does not fail
        // merely because its TelegramAccount was removed or recreated.
        $user = $this->registration->begin($update);

        try {
            $user = $this->registration->verifyPhone($user, $update);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? $this->registration->message('registration.phone_invalid');
            $this->send($update, (string) $message);
            throw new InvalidArgumentException((string) $message, 0, $exception);
        }

        $this->mainMenu->show($update);

        return ['done' => true, 'data' => ['accepted' => true, 'phone_verified' => true]];
    }

    private function send(TelegramUpdate $update, string $text, ?array $replyMarkup = null): mixed
    {
        return $this->messages->respond($update, $text, $replyMarkup);
    }
}
