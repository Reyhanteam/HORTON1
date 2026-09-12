<?php

namespace App\Policies;

use App\Models\AdminUser;

final class AdminUserPolicy
{
    public function viewAny(AdminUser $admin): bool
    {
        return $admin->hasPermission('admin.users.view');
    }

    public function view(AdminUser $admin, AdminUser $target): bool
    {
        return $admin->hasPermission('admin.users.view') && $admin->id === $target->id || $admin->hasPermission('admin.users.manage');
    }

    public function manage(AdminUser $admin): bool
    {
        return $admin->hasPermission('admin.users.manage');
    }
}
