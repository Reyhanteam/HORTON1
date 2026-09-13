<?php

declare(strict_types=1);

namespace App\Telegram\Controllers;

use App\Contracts\BotMessageStore;
use App\Models\SupportTicket;
use App\Services\Registration\RegistrationService;
use App\Services\Support\SupportTicketService;
use App\Services\Telegram\BotMessageResponder;
use App\Telegram\Conversations\SupportConversation;
use ReyhanTeam\TelegramBotRouter\Conversation\ConversationManager;
use ReyhanTeam\TelegramBotRouter\Keyboard\Keyboard;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

final class SupportController
{
    public const ENTRY = 'support:entry';
    public const FAQ = 'support:faq';
    public const FAQ_ACCEPTED = 'support:faq-accepted';
    public const DEPARTMENT_PREFIX = 'support:department:';
    public const SENSITIVITY_PREFIX = 'support:sensitivity:';
    public const BACK = 'support:back';

    public function __construct(
        private readonly RegistrationService $registration,
        private readonly BotMessageStore $messages,
        private readonly BotMessageResponder $responder,
        private readonly SupportTicketService $support,
        private readonly ConversationManager $conversations,
    ) {
    }

    public function entry(TelegramUpdate $update): mixed
    {
        return $this->responder->respond(
            $update,
            $this->message('support.faq_gate'),
            Keyboard::inline()
                ->callbackButton($this->button('support.faq_not_read'), self::FAQ)
                ->callbackButton($this->button('support.faq_read'), self::FAQ_ACCEPTED)
                ->toArray(),
        );
    }

    public function faq(TelegramUpdate $update): mixed
    {
        $contents = $this->support->faq();
        $text = $contents->isEmpty()
            ? $this->message('support.faq_empty')
            : collect([$this->message('support.faq_title')])
                ->merge($contents->map(fn ($item): string => "\n<b>{$item->title}</b>\n{$item->body}"))
                ->implode("\n");

        return $this->responder->respond(
            $update,
            $text,
            Keyboard::inline()
                ->callbackButton($this->button('support.continue'), self::FAQ_ACCEPTED)
                ->callbackButton($this->button('menu.back_button'), 'menu:home')
                ->toArray(),
        );
    }

    public function begin(TelegramUpdate $update): mixed
    {
        $departments = $this->support->departments();
        if ($departments->isEmpty()) {
            return $this->responder->respond($update, $this->message('support.no_departments'));
        }

        $this->conversations->start(
            $update,
            SupportConversation::name(),
            SupportConversation::steps(),
            (int) config('telegram-bot-router.conversation.ttl', 3600),
            [],
            [],
            config('telegram-bot-router.conversation.cache_store'),
        );

        return $this->responder->respond(
            $update,
            $this->message('support.choose_department'),
            $this->departmentKeyboard(),
        );
    }

    public function department(TelegramUpdate $update, array $data): array
    {
        $callback = (string) $update->callbackQueryData();
        if (!str_starts_with($callback, self::DEPARTMENT_PREFIX)) {
            throw new \InvalidArgumentException('Invalid support department selection.');
        }

        $departmentId = (int) substr($callback, strlen(self::DEPARTMENT_PREFIX));
        if ($this->support->departments()->firstWhere('id', $departmentId) === null) {
            throw new \InvalidArgumentException('Invalid support department selection.');
        }

        $this->responder->respond(
            $update,
            $this->message('support.choose_sensitivity'),
            Keyboard::inline()
                ->callbackButton($this->button('support.sensitivity_low'), self::SENSITIVITY_PREFIX.'low')
                ->callbackButton($this->button('support.sensitivity_normal'), self::SENSITIVITY_PREFIX.'normal')
                ->callbackButton($this->button('support.sensitivity_high'), self::SENSITIVITY_PREFIX.'high')
                ->toArray(),
        );

        return ['data' => [...$data, 'department_id' => $departmentId]];
    }

    public function sensitivity(TelegramUpdate $update, array $data): array
    {
        $callback = (string) $update->callbackQueryData();
        if (!str_starts_with($callback, self::SENSITIVITY_PREFIX)) {
            throw new \InvalidArgumentException('Invalid support sensitivity selection.');
        }

        $sensitivity = substr($callback, strlen(self::SENSITIVITY_PREFIX));
        if (!in_array($sensitivity, ['low', 'normal', 'high'], true)) {
            throw new \InvalidArgumentException('Invalid support sensitivity selection.');
        }

        $this->responder->respond($update, $this->message('support.write_message'));
        return ['data' => [...$data, 'sensitivity' => $sensitivity]];
    }

    public function message(TelegramUpdate $update, array $data): array
    {
        $user = $this->registration->userForUpdate($update);
        $payload = $this->extractMessage($update);
        $ticket = $this->support->createTicket(
            $user,
            (int) ($data['department_id'] ?? 0),
            (string) ($data['sensitivity'] ?? 'normal'),
            $payload['text'],
            $payload['attachments'],
        );

        $this->responder->respond(
            $update,
            $this->render('support.created', ['{ticket_id}' => $ticket->id]),
            Keyboard::inline()->callbackButton($this->button('menu.back_button'), 'menu:home')->toArray(),
        );

        return ['done' => true, 'data' => [...$data, 'ticket_id' => $ticket->id]];
    }

    public function listTickets(TelegramUpdate $update): mixed
    {
        $user = $this->registration->userForUpdate($update);
        $tickets = SupportTicket::query()->where('user_id', $user->id)->latest('id')->limit(10)->get();
        $text = $tickets->isEmpty()
            ? $this->message('support.no_tickets')
            : $this->render('support.ticket_list', ['{tickets}' => $tickets->map(fn ($ticket) => "#{$ticket->id} — {$ticket->status}")->implode("\n")]);

        return $this->responder->respond($update, $text, Keyboard::inline()->callbackButton($this->button('menu.back_button'), 'menu:home')->toArray());
    }

    private function departmentKeyboard(): array
    {
        $keyboard = Keyboard::inline();
        foreach ($this->support->departments() as $department) {
            $keyboard->callbackButton($department->name, self::DEPARTMENT_PREFIX.$department->id)->row();
        }
        return $keyboard->toArray();
    }

    private function extractMessage(TelegramUpdate $update): array
    {
        $message = $update->originalUpdate()->message ?? null;
        if ($message === null) return ['text' => trim((string) $update->text()), 'attachments' => []];

        $text = $message->text ?? $message->caption ?? null;
        $attachments = [];

        $photo = $message->photo ?? null;
        if ($photo !== null) {
            $sizes = is_object($photo) ? get_object_vars($photo) : (is_array($photo) ? $photo : []);
            $last = $sizes === [] ? null : end($sizes);
            $fileId = is_object($last) ? ($last->file_id ?? null) : (is_array($last) ? ($last['file_id'] ?? null) : null);
            if (is_string($fileId) && $fileId !== '') $attachments[] = ['type' => 'photo', 'file_id' => $fileId];
        }

        foreach (['video' => 'video', 'document' => 'document'] as $property => $type) {
            $value = $message->{$property} ?? null;
            $fileId = is_object($value) ? ($value->file_id ?? null) : (is_array($value) ? ($value['file_id'] ?? null) : null);
            if (is_string($fileId) && $fileId !== '') $attachments[] = ['type' => $type, 'file_id' => $fileId];
        }

        return ['text' => is_string($text) ? trim($text) : null, 'attachments' => $attachments];
    }

    private function message(string $key): string
    {
        $value = $this->messages->get($key, type: 'message');
        if ($value === null) throw new \LogicException("Missing bot message: {$key}");
        return $value;
    }

    private function button(string $key): string
    {
        $value = $this->messages->get($key, type: 'button');
        if ($value === null) throw new \LogicException("Missing bot button message: {$key}");
        return $value;
    }

    private function render(string $key, array $replace): string
    {
        return strtr($this->message($key), array_map(static fn ($value): string => (string) $value, $replace));
    }
}
