<?php

namespace App\Policies;

use App\Models\ModifierGroup;
use App\Models\User;

/**
 * Permission names are checked against the active tenant (spatie teams).
 * Super admins bypass every check through the Gate::before hook.
 */
class ModifierGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('menu.view');
    }

    public function view(User $user, ModifierGroup $group): bool
    {
        return $user->can('menu.view');
    }

    public function create(User $user): bool
    {
        return $user->can('menu.manage');
    }

    public function update(User $user, ModifierGroup $group): bool
    {
        return $user->can('menu.manage');
    }

    public function delete(User $user, ModifierGroup $group): bool
    {
        return $user->can('menu.manage');
    }
}
