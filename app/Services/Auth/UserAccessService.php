<?php

namespace App\Services\Auth;

use App\Contracts\UserAccessChecker;
use App\Enums\UserStatus;
use App\Models\User;

final class UserAccessService implements UserAccessChecker
{
    public function canAccessBot(User $user): bool
    {
        return $user->status === UserStatus::Active
            && $user->telegramAccount?->is_active === true;
    }
}
