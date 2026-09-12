<?php

namespace App\Services\Auth;

use App\Contracts\UserLifecycle;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

abstract class AbstractUserLifecycle implements UserLifecycle
{
    final public function register(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $user = $this->createUser($attributes);

            return $this->setStatus($user, UserStatus::Pending);
        });
    }

    final public function activate(User $user): User
    {
        return $this->setStatus($user, UserStatus::Active);
    }

    final public function deactivate(User $user): User
    {
        return $this->setStatus($user, UserStatus::Inactive);
    }

    final public function block(User $user): User
    {
        return $this->setStatus($user, UserStatus::Blocked);
    }

    final public function setStatus(User $user, UserStatus $status): User
    {
        if ($user->status === $status->value) {
            return $user;
        }

        if (! $this->transitionAllowed((string) $user->status, $status)) {
            throw ValidationException::withMessages([
                'status' => "User status transition to {$status->value} is not allowed.",
            ]);
        }

        $user->forceFill(['status' => $status->value])->save();

        return $user->refresh();
    }

    abstract protected function createUser(array $attributes): User;

    protected function transitionAllowed(string $from, UserStatus $to): bool
    {
        return match ($from) {
            '', UserStatus::Pending->value => in_array($to, [UserStatus::Active, UserStatus::Blocked, UserStatus::Inactive], true),
            UserStatus::Active->value => in_array($to, [UserStatus::Inactive, UserStatus::Blocked], true),
            UserStatus::Inactive->value => in_array($to, [UserStatus::Active, UserStatus::Blocked], true),
            UserStatus::Blocked->value => false,
            default => false,
        };
    }
}
