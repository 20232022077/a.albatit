<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->id !== $target->id && $user->hasPermission('users.update');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->id !== $target->id && $user->hasPermission('users.delete');
    }
}
