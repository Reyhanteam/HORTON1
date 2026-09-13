<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UserWalletManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['users.view', 'users.manage', 'wallet.view', 'wallet.credit', 'wallet.debit', 'wallet.manage']): AdminUser
    {
        $admin = AdminUser::query()->create([
            'name' => 'Test Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);

        $role = Role::query()->create(['name' => 'Test Role', 'slug' => 'test-role']);
        $permissionIds = collect($permissions)->map(fn (string $slug) => Permission::query()->create([
            'name' => $slug,
            'slug' => $slug,
        ])->id);
        $role->permissions()->sync($permissionIds);
        $admin->roles()->attach($role);

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_admin_can_search_and_view_user_domain_data(): void
    {
        $this->admin(['users.view']);
        $user = User::factory()->create(['name' => 'Alice Example', 'email' => 'alice@example.test']);

        $this->getJson('/admin/users?q=Alice')
            ->assertOk()
            ->assertJsonPath('data.0.id', $user->id);

        $this->getJson('/admin/users/'.$user->id)
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_admin_can_change_user_status_and_audit_it(): void
    {
        $this->admin(['users.manage']);
        $user = User::factory()->create();

        $this->patchJson('/admin/users/'.$user->id.'/status', ['status' => 'blocked'])
            ->assertOk()
            ->assertJsonPath('status', 'blocked');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.status_changed',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);
    }

    public function test_admin_wallet_credit_and_debit_use_application_wallet_service(): void
    {
        $this->admin(['wallet.view', 'wallet.credit', 'wallet.debit']);
        $user = User::factory()->create();

        $this->postJson('/admin/users/'.$user->id.'/wallet/credit', [
            'amount' => 100000,
            'description' => 'Manual admin credit',
            'idempotency_key' => 'admin-credit-1',
        ])->assertCreated();

        $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'balance' => 100000]);

        $this->postJson('/admin/users/'.$user->id.'/wallet/debit', [
            'amount' => 25000,
            'description' => 'Manual admin debit',
            'idempotency_key' => 'admin-debit-1',
        ])->assertCreated();

        $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'balance' => 75000]);
        $this->assertDatabaseCount('wallet_transactions', 2);
    }

    public function test_wallet_mutations_require_separate_permissions(): void
    {
        $this->admin(['wallet.view']);
        $user = User::factory()->create();
        Wallet::query()->create(['user_id' => $user->id, 'balance' => 50000, 'currency' => 'IRR', 'status' => 'active']);

        $this->postJson('/admin/users/'.$user->id.'/wallet/credit', [
            'amount' => 1000,
            'description' => 'Should be forbidden',
        ])->assertForbidden();
    }
}
