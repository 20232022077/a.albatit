<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_guest_is_redirected_to_login_from_admin_routes(): void
    {
        $this->get('/admin/books')->assertRedirect(route('login'));
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_permission_gets_403(): void
    {
        $user = $this->userWithNoPermissions();

        $this->actingAs($user)->get('/admin/books')->assertForbidden();
        $this->actingAs($user)->get('/admin/settings')->assertForbidden();
        $this->actingAs($user)->get('/admin/backups')->assertForbidden();
    }

    public function test_user_with_specific_permission_can_access_matching_section(): void
    {
        $user = $this->userWithPermissions(['content.view']);

        $this->actingAs($user)->get('/admin/books')->assertOk();
    }

    public function test_super_admin_bypasses_every_permission_check(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get('/admin/books')->assertOk();
        $this->actingAs($admin)->get('/admin/settings')->assertOk();
        $this->actingAs($admin)->get('/admin/backups')->assertOk();
        $this->actingAs($admin)->get('/admin/activity-logs')->assertOk();
    }

    public function test_deactivating_a_user_mid_session_ends_their_access_immediately(): void
    {
        $user = $this->userWithPermissions(['content.view']);

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->update(['is_active' => false]);

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * Gate::before grants a real super-admin an unconditional bypass, so
     * this self-edit guard is only meaningfully testable on a non-super-admin
     * account that otherwise has full users.* permissions.
     */
    public function test_user_cannot_update_or_delete_their_own_account(): void
    {
        $manager = $this->userWithPermissions(['users.view', 'users.update', 'users.delete']);

        $this->actingAs($manager)
            ->put(route('admin.users.update', $manager), ['name' => 'اسم جديد', 'email' => $manager->email, 'is_active' => 1])
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('admin.users.destroy', $manager))
            ->assertForbidden();
    }

    public function test_super_admin_role_cannot_be_edited_or_deleted_via_policy(): void
    {
        $manager = $this->userWithPermissions(['roles.view', 'roles.update', 'roles.delete']);
        $superAdminRole = Role::where('name', 'super-admin')->firstOrFail();

        $this->actingAs($manager)
            ->delete(route('admin.roles.destroy', $superAdminRole))
            ->assertForbidden();
    }

    /**
     * Regression test for a real privilege-escalation bug found during the
     * command-27 security review: a user with only users.create/users.update
     * (no roles.* permission at all) could create an account and assign it
     * the super-admin role directly through role_ids[]. Fixed in
     * UserController::assignableRoleIds() — the id is now silently dropped
     * unless the acting user is already a super-admin.
     */
    public function test_non_super_admin_cannot_grant_super_admin_role_to_a_new_user(): void
    {
        $manager = $this->userWithPermissions(['users.view', 'users.create', 'users.update']);
        $superAdminRole = Role::where('name', 'super-admin')->firstOrFail();

        $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Puppet Account',
            'email' => 'puppet@example.com',
            'password' => 'PuppetPassword123',
            'password_confirmation' => 'PuppetPassword123',
            'role_ids' => [$superAdminRole->id],
        ])->assertRedirect(route('admin.users.index'));

        $puppet = User::where('email', 'puppet@example.com')->firstOrFail();
        $this->assertFalse($puppet->hasRole('super-admin'));
    }

    /**
     * Regression test for the second half of the same bug class: a user
     * with only roles.create/roles.update (but none of the permissions
     * being granted) could build a brand-new role holding every permission
     * — functionally a second super-admin — and hand it to another
     * account. Fixed in RoleController::grantablePermissionIds(): a role
     * manager can never grant a permission they don't themselves hold.
     */
    public function test_non_super_admin_cannot_grant_permissions_they_do_not_hold_via_a_new_role(): void
    {
        $manager = $this->userWithPermissions(['roles.view', 'roles.create', 'roles.update']);
        $allPermissionIds = Permission::pluck('id')->all();

        $this->actingAs($manager)->post(route('admin.roles.store'), [
            'name' => 'fake-super-role',
            'display_name' => 'Fake Super Role',
            'permission_ids' => $allPermissionIds,
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::where('name', 'fake-super-role')->firstOrFail();
        $grantedNames = $role->permissions()->pluck('name')->all();

        // Only the permissions the manager already held may come through —
        // never the ones exclusive to a real super-admin.
        sort($grantedNames);
        $this->assertSame(['roles.create', 'roles.update', 'roles.view'], $grantedNames);
        $this->assertNotContains('settings.manage', $grantedNames);
        $this->assertNotContains('backups.manage', $grantedNames);
        $this->assertNotContains('users.delete', $grantedNames);
    }
}
