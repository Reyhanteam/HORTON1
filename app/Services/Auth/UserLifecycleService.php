<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class UserLifecycleService extends AbstractUserLifecycle
{
    protected function createUser(array $attributes): User
    {
        if (isset($attributes['password'])) {
            $attributes['password'] = Hash::make($attributes['password']);
        }

        return User::query()->create($attributes);
    }
}
