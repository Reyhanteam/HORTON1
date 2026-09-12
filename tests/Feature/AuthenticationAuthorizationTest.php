<?php

namespace Tests\Feature;

use App\Contracts\AdminAuthenticator;
use App\Contracts\UserAccessChecker;
use App\Contracts\UserLifecycle;
use App\Enums\UserStatus;
use App\Models\AdminUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TelegramAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthenticationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_guard_and_contract_are_separate_from_user_guard(): void
    {
        $this->assertFalse(auth('admin')->check());
        $this->assertFalse(auth('web')->check());
        $this->assertInstanceOf(AdminAuthenticator::class, app(AdminAuthenticator::class));
        $this->assertInstanceOf(UserLifecycle::class, app(UserLifecycle::class));
        $this->assertInstanceOf(UserAccessChecker::class, app(UserAccessChecker::class));
    }

    public function test_admin_login_requires_active_admin(): void
    {
        $admin = AdminUser::factory()->create(['email' => 'admin@example.test', 'password' => 'secret']);

        $response = $this->postJson('/admin/login', [
            'email' => 'admin@example.test',
            'password' => 'secret',
        ]);

        $response->assertOk();
        $this->assertAuthenticatedAs($admin, 'admin');

        $admin->update(['status' => 'inactive']);
        auth('admin')->logout();

        $this->postJson('/admin/login', [
            'email' => 'admin@example.test',
            'password' => 'secret',
        ])->assertUnprocessable();
    }

    public function test_admin_permission_middleware_denies_without_permission(): void
    {
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin');

        $this->getJson('/admin/users')->assertForbidden();

        $role = Role::create(['name' => 'Support', 'slug' => 'support']);
        $permission = Permission::create(['name' => 'View users', 'slug' => 'users.view']);
        $role->permissions()->attach($permission);
        $admin->roles()->attach($role);

        $this->getJson('/admin/users')->assertOk();
    }

    public function test_user_lifecycle_transitions_and_bot_access(): void
    {
        $lifecycle = app(UserLifecycle::class);
        $user = $lifecycle->register(['name' => 'Test User', 'username' => 'tester', 'password' => 'secret']);

        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertFalse(app(UserAccessChecker::class)->canAccessBot($user->fresh()));

        TelegramAccount::create([
            'user_id' => $user->id,
            'telegram_user_id' => 123456789,
            'is_active' => true,
        ]);

        $lifecycle->activate($user);
        $this->assertTrue(app(UserAccessChecker::class)->canAccessBot($user->fresh()));

        $lifecycle->block($user);
        $this->assertFalse(app(UserAccessChecker::class)->canAccessBot($user->fresh()));
    }

    public function test_blocked_user_cannot_be_reactivated(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active->value]);
        $lifecycle = app(UserLifecycle::class);

        $lifecycle->block($user);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $lifecycle->activate($user);
    }
}
