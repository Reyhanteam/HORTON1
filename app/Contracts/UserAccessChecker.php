<?php

namespace App\Contracts;

use App\Models\User;

interface UserAccessChecker
{
    public function canAccessBot(User $user): bool;
}
