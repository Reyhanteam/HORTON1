<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\BotSetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password'],
        );

        $permissions = [
            'users.view', 'users.manage',
            'wallet.view', 'wallet.credit', 'wallet.debit', 'wallet.manage',
            'support.departments.view', 'support.departments.manage',
            'support.content.view', 'support.content.manage',
            'support.tickets.view', 'support.tickets.reply', 'support.tickets.manage',
        ];

        foreach ($permissions as $slug) {
            Permission::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $slug, 'description' => 'HORTON dashboard permission: '.$slug],
            );
        }

        $role = Role::query()->firstOrCreate(
            ['slug' => 'support-manager'],
            ['name' => 'Support & User Manager', 'description' => 'Manage users, wallets and support.'],
        );

        $role->permissions()->syncWithoutDetaching(
            Permission::query()->whereIn('slug', $permissions)->pluck('id')
        );

        $adminEmail = env('HORTON_ADMIN_EMAIL');
        if ($adminEmail) {
            $adminUser = User::query()->where('email', $adminEmail)->first();

            if ($adminUser) {
                $admin = AdminUser::query()->updateOrCreate(
                    ['email' => $adminUser->email],
                    [
                        'name' => $adminUser->name,
                        'password' => Hash::make(bin2hex(random_bytes(32))),
                        'status' => $adminUser->status,
                    ],
                );

                $admin->roles()->syncWithoutDetaching([$role->id]);
            }
        }

        BotSetting::query()->updateOrCreate(
            ['key' => 'features.phone_verification'],
            ['value' => 'true', 'type' => 'boolean', 'is_public' => false],
        );

        BotSetting::query()->updateOrCreate(
            ['key' => 'features.channel_membership'],
            ['value' => 'false', 'type' => 'boolean', 'is_public' => false],
        );
    }
}
