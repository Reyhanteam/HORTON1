<?php

declare(strict_types=1);

namespace App\Telegram\Controllers;

use App\Services\Registration\RegistrationService;
use App\Telegram\Conversations\RegistrationConversation;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use ReyhanTeam\TelegramBotRouter\Conversation\ConversationManager;
use ReyhanTeam\TelegramBotRouter\Facades\BOT;
use ReyhanTeam\TelegramBotRouter\Keyboard\Keyboard;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

final class RegistrationController
{
    public function __construct(
        private readonly RegistrationService $registration,
        private readonly ConversationManager $conversations,
    ) {}

    public function start(TelegramUpdate $update): mixed
    {
        $user = $this->registration->begin($update);

        if ($user->isActive()) {
            return $this->send($update, $this->registration->render('registration.already_active', [
                '{name}' => $user->name ?? '',
            ]));
        }

        $this->conversations->start(
            $update,
            RegistrationConversation::name(),
            RegistrationConversation::steps(),
            (int) config('telegram-bot-router.conversation.ttl', 3600),
        );

        return $this->send(
            $update,
            $this->registration->render('registration.rules', ['{name}' => $user->name ?? '']),
            Keyboard::inline()
                ->callbackButton($this->registration->message('registration.accept_button'), RegistrationService::ACCEPT)
                ->row()
                ->callbackButton($this->registration->message('registration.decline_button'), RegistrationService::DECLINE)
                ->toArray(),
        );
    }

    public function acceptance(TelegramUpdate $update, mixed $input): array
    {
        $user = $this->registration->userForUpdate($update);
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
            $this->send(
                $update,
                $this->registration->message('registration.success'),
                Keyboard::reply()->remove()->toArray(),
            );

            return ['done' => true, 'data' => ['accepted' => true, 'phone_verified' => false]];
        }

        $this->send(
            $update,
            $this->registration->message('registration.phone_prompt'),
            [
                'keyboard' => [[[
                    'text' => $this->registration->message('registration.share_phone_button'),
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
        $user = $this->registration->userForUpdate($update);

        try {
            $user = $this->registration->verifyPhone($user, $update);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? $this->registration->message('registration.phone_invalid');
            $this->send($update, (string) $message);
            throw new InvalidArgumentException((string) $message, 0, $exception);
        }

        $this->send(
            $update,
            $this->registration->render('registration.success', ['{name}' => $user->name ?? '']),
            Keyboard::reply()->remove()->toArray(),
        );

        return ['done' => true, 'data' => ['accepted' => true, 'phone_verified' => true]];
    }

    private function send(TelegramUpdate $update, string $text, ?array $replyMarkup = null): mixed
    {
        $payload = [
            'chat_id' => $update->chatId(),
            'text' => $text,
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        return BOT::sendMessage($update->chatId(), $text, replyMarkup:$replyMarkup);
    }
}
