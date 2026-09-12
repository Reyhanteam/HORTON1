<?php

namespace App\Contracts;

use App\Enums\UserStatus;
use App\Models\User;

interface UserLifecycle
{
    public function register(array $attributes): User;

    public function activate(User $user): User;

    public function deactivate(User $user): User;

    public function block(User $user): User;

    public function setStatus(User $user, UserStatus $status): User;
}
