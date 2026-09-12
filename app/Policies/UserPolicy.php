<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\User;

final class UserPolicy
{
    public function view(AdminUser $admin, User $user): bool
    {
        return $admin->hasPermission('users.view');
    }

    public function manage(AdminUser $admin, User $user): bool
    {
        return $admin->hasPermission('users.manage');
    }
}
