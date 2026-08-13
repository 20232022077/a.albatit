<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $role->name !== 'super-admin' && $user->hasPermission('roles.update');
    }

    public function delete(User $user, Role $role): bool
    {
        return $role->name !== 'super-admin' && $user->hasPermission('roles.delete');
    }
}
