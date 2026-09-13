<?php

declare(strict_types=1);

namespace App\Services\Registration;

use App\Actions\Users\RegisterUserAction;
use App\Contracts\BotMessageStore;
use App\Contracts\FeatureManager;
use App\Contracts\UserLifecycle;
use App\DTOs\CreateUserData;
use App\Enums\Feature;
use App\Enums\UserStatus;
use App\Models\TelegramAccount;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use ReyhanTeam\TelegramBotRouter\TelegramUpdate;

final class RegistrationService
{
    public const CONVERSATION = 'registration';
    public const ACCEPT = 'registration:accept';
    public const DECLINE = 'registration:decline';

    public function __construct(
        private readonly RegisterUserAction $registerUser,
        private readonly UserLifecycle $lifecycle,
        private readonly FeatureManager $features,
        private readonly BotMessageStore $messages,
        private readonly DatabaseManager $db,
        private readonly PhoneNumberNormalizer $phoneNormalizer,
    ) {}

    public function begin(TelegramUpdate $update): User
    {
        $telegramUserId = $this->telegramUserId($update);

        $account = TelegramAccount::query()
            ->with('user')
            ->where('telegram_user_id', $telegramUserId)
            ->first();

        if ($account !== null) {
            $user = $account->user;
            $this->syncTelegramAccount($account, $update);

            if ($user->status === UserStatus::Blocked) {
                throw ValidationException::withMessages([
                    'registration' => $this->message('registration.blocked'),
                ]);
            }

            return $user;
        }

        $from = $update->message?->from;
        $user = $this->registerUser->execute(new CreateUserData(
            name: $this->displayName($from),
            username: isset($from->username) ? (string) $from->username : null,
            locale: isset($from->language_code) ? (string) $from->language_code : null,
        ));

        TelegramAccount::query()->create([
            'user_id' => $user->id,
            'telegram_user_id' => $telegramUserId,
            'username' => isset($from->username) ? (string) $from->username : null,
            'first_name' => isset($from->first_name) ? (string) $from->first_name : null,
            'last_name' => isset($from->last_name) ? (string) $from->last_name : null,
            'language_code' => isset($from->language_code) ? (string) $from->language_code : null,
            'is_bot' => (bool) ($from->is_bot ?? false),
            'is_active' => true,
            'last_seen_at' => now(),
        ]);

        return $user->refresh();
    }

    public function userForUpdate(TelegramUpdate $update): User
    {
        $account = TelegramAccount::query()
            ->with('user')
            ->where('telegram_user_id', $this->telegramUserId($update))
            ->first();

        if ($account === null) {
            throw ValidationException::withMessages([
                'registration' => $this->message('registration.not_started'),
            ]);
        }

        if ($account->user->status === UserStatus::Blocked) {
            throw ValidationException::withMessages([
                'registration' => $this->message('registration.blocked'),
            ]);
        }

        return $account->user;
    }

    public function accept(User $user): array
    {
        if ($user->status === UserStatus::Blocked) {
            throw ValidationException::withMessages([
                'registration' => $this->message('registration.blocked'),
            ]);
        }

        if (!$this->phoneVerificationEnabled()) {
            return ['done' => true, 'user' => $this->lifecycle->activate($user)];
        }

        return ['done' => false, 'user' => $user];
    }

    public function decline(User $user): array
    {
        return ['done' => true, 'user' => $user];
    }

    public function verifyPhone(User $user, TelegramUpdate $update): User
    {
        if (!$this->phoneVerificationEnabled()) {
            return $this->lifecycle->activate($user);
        }

        $phone = $this->validatedContactPhone($update);

        try {
            return $this->db->transaction(function () use ($user, $phone): User {
                $existing = User::query()
                    ->where('phone', $phone)
                    ->where($user->getKeyName(), '!=', $user->getKey())
                    ->lockForUpdate()
                    ->exists();

                if ($existing) {
                    throw ValidationException::withMessages([
                        'phone' => $this->message('registration.phone_taken'),
                    ]);
                }

                $user = User::query()->lockForUpdate()->findOrFail($user->getKey());
                $user->forceFill([
                    'phone' => $phone,
                    'phone_verified_at' => now(),
                ])->save();

                $account = TelegramAccount::query()
                    ->where('user_id', $user->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($account === null) {
                    throw ValidationException::withMessages([
                        'registration' => $this->message('registration.not_started'),
                    ]);
                }

                $account->forceFill([
                    'phone' => $phone,
                ])->save();

                return $this->lifecycle->activate($user->refresh());
            });
        } catch (QueryException $exception) {
            if ($this->isDuplicatePhoneException($exception)) {
                throw ValidationException::withMessages([
                    'phone' => $this->message('registration.phone_taken'),
                ]);
            }

            throw $exception;
        }
    }

    public function message(string $key): string
    {
        return $this->messages->get($key, default: $key, type: 'message') ?? $key;
    }

    public function button(string $key): string
    {
        return $this->messages->get($key, default: $key, type: 'button') ?? $key;
    }

    public function render(string $key, array $replace = []): string
    {
        return strtr(
            $this->message($key),
            array_map(static fn ($value): string => (string) $value, $replace),
        );
    }

    public function phoneVerificationEnabled(): bool
    {
        return $this->features->enabled(Feature::PhoneVerification->value, true);
    }

    public function telegramUserId(TelegramUpdate $update): int
    {
        $id = $update->userId();
        if ($id === null) {
            throw ValidationException::withMessages([
                'telegram' => $this->message('registration.telegram_user_required'),
            ]);
        }

        return (int) $id;
    }

    private function validatedContactPhone(TelegramUpdate $update): string
    {
        $contact = $update->message?->contact;

        if ($contact === null) {
            throw ValidationException::withMessages([
                'phone' => $this->message('registration.phone_required'),
            ]);
        }

        $telegramUserId = $this->telegramUserId($update);
        $contactUserId = $contact->user_id ?? null;

        if ($contactUserId === null || (int) $contactUserId !== $telegramUserId) {
            throw ValidationException::withMessages([
                'phone' => $this->message('registration.phone_owner_mismatch'),
            ]);
        }

        try {
            return $this->phoneNormalizer->normalize((string) ($contact->phone_number ?? ''));
        } catch (ValidationException) {
            throw ValidationException::withMessages([
                'phone' => $this->message('registration.phone_invalid'),
            ]);
        }
    }

    private function syncTelegramAccount(TelegramAccount $account, TelegramUpdate $update): void
    {
        $from = $update->message?->from;

        $account->forceFill([
            'username' => isset($from->username) ? (string) $from->username : $account->username,
            'first_name' => isset($from->first_name) ? (string) $from->first_name : $account->first_name,
            'last_name' => isset($from->last_name) ? (string) $from->last_name : $account->last_name,
            'language_code' => isset($from->language_code) ? (string) $from->language_code : $account->language_code,
            'last_seen_at' => now(),
        ])->save();
    }

    private function displayName(?object $from): ?string
    {
        if ($from === null) {
            return null;
        }

        $name = trim(implode(' ', array_filter([
            isset($from->first_name) ? (string) $from->first_name : null,
            isset($from->last_name) ? (string) $from->last_name : null,
        ])));

        return $name !== '' ? $name : null;
    }

    private function isDuplicatePhoneException(QueryException $exception): bool
    {
        return str_contains(strtolower($exception->getMessage()), 'phone');
    }
}
