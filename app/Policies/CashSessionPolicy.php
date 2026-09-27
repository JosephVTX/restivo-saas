<?php

namespace App\Policies;

use App\Models\CashSession;
use App\Models\User;

/**
 * Permission names are checked against the active tenant (spatie teams).
 * Super admins bypass every check through the Gate::before hook.
 */
class CashSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cash.view');
    }

    public function view(User $user, CashSession $cashSession): bool
    {
        return $user->can('cash.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cash.manage');
    }

    public function update(User $user, CashSession $cashSession): bool
    {
        return $user->can('cash.manage');
    }

    public function close(User $user, CashSession $cashSession): bool
    {
        return $user->can('cash.manage');
    }

    public function movement(User $user, CashSession $cashSession): bool
    {
        return $user->can('cash.manage');
    }
}
