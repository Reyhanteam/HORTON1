<?php

namespace App\Services\Auth;

use App\Contracts\UserLifecycle;
use App\Enums\UserStatus;
use App\Events\UserRegistered;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Validation\ValidationException;

abstract class AbstractUserLifecycle implements UserLifecycle
{
    public function __construct(protected readonly DatabaseManager $db) {}

    final public function register(array $attributes): User
    {
        return $this->db->transaction(function () use ($attributes): User {
            $user = $this->createUser($attributes);
            $user = $this->setStatus($user, UserStatus::Pending);
            UserRegistered::dispatch($user);
            return $user;
        });
    }

    final public function activate(User $user): User { return $this->setStatus($user, UserStatus::Active); }
    final public function deactivate(User $user): User { return $this->setStatus($user, UserStatus::Inactive); }
    final public function block(User $user): User { return $this->setStatus($user, UserStatus::Blocked); }

    final public function setStatus(User $user, UserStatus $status): User
    {
        $current = $user->status instanceof UserStatus ? $user->status : UserStatus::tryFrom((string)$user->status);
        if ($current === $status) return $user;
        if (!$this->transitionAllowed($current, $status)) throw ValidationException::withMessages(['status'=>"User status transition to {$status->value} is not allowed."]);
        $user->forceFill(['status'=>$status])->save();
        return $user->refresh();
    }

    abstract protected function createUser(array $attributes): User;

    protected function transitionAllowed(?UserStatus $from, UserStatus $to): bool
    {
        return match ($from) {
            null, UserStatus::Pending => in_array($to,[UserStatus::Active,UserStatus::Blocked,UserStatus::Inactive],true),
            UserStatus::Active => in_array($to,[UserStatus::Inactive,UserStatus::Blocked],true),
            UserStatus::Inactive => in_array($to,[UserStatus::Active,UserStatus::Blocked],true),
            UserStatus::Blocked => false,
        };
    }
}
