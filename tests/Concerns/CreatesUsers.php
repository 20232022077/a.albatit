<?php

namespace Tests\Concerns;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Test-only user/role fixtures. Assumes AccessControlSeeder has already run
 * (seeds the full permission list and the super-admin role with all of
 * them) — call $this->seed(AccessControlSeeder::class) in setUp().
 */
trait CreatesUsers
{
    protected function superAdmin(array $attributes = []): User
    {
        $user = User::factory()->create(['is_active' => true, ...$attributes]);
        $role = Role::where('name', 'super-admin')->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user;
    }

    protected function userWithPermissions(array $permissions, array $attributes = []): User
    {
        $role = Role::create(['name' => 'test-role-'.Str::random(8), 'display_name' => 'صلاحيات اختبار']);
        $role->permissions()->sync(Permission::whereIn('name', $permissions)->pluck('id'));

        $user = User::factory()->create(['is_active' => true, ...$attributes]);
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user;
    }

    protected function userWithNoPermissions(array $attributes = []): User
    {
        return User::factory()->create(['is_active' => true, ...$attributes]);
    }
}
