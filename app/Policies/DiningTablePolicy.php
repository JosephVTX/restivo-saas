<?php

namespace App\Policies;

use App\Models\DiningTable;
use App\Models\User;

/**
 * Permission names are checked against the active tenant (spatie teams).
 * Super admins bypass every check through the Gate::before hook.
 */
class DiningTablePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tables.view');
    }

    public function view(User $user, DiningTable $table): bool
    {
        return $user->can('tables.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tables.manage');
    }

    public function update(User $user, DiningTable $table): bool
    {
        return $user->can('tables.manage');
    }

    public function delete(User $user, DiningTable $table): bool
    {
        return $user->can('tables.manage');
    }
}
