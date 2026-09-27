<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Zone;

/**
 * Permission names are checked against the active tenant (spatie teams).
 * Super admins bypass every check through the Gate::before hook.
 */
class ZonePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tables.view');
    }

    public function view(User $user, Zone $zone): bool
    {
        return $user->can('tables.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tables.manage');
    }

    public function update(User $user, Zone $zone): bool
    {
        return $user->can('tables.manage');
    }

    public function delete(User $user, Zone $zone): bool
    {
        return $user->can('tables.manage');
    }
}
