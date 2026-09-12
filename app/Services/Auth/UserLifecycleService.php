<?php

namespace App\Services\Auth;

use App\Enums\UserStatus;
use App\Models\User;

final class UserLifecycleService extends AbstractUserLifecycle
{
    protected function createUser(array $attributes): User
    {
        $attributes['status'] = UserStatus::Pending;

        return User::query()->create($attributes);
    }
}
