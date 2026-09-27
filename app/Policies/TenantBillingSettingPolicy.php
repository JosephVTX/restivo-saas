<?php

namespace App\Policies;

use App\Models\TenantBillingSetting;
use App\Models\User;

/**
 * Permission names are checked against the active tenant (spatie teams).
 * Super admins bypass every check through the Gate::before hook.
 */
class TenantBillingSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('billing.manage');
    }

    public function view(User $user, TenantBillingSetting $settings): bool
    {
        return $user->can('billing.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('billing.manage');
    }

    public function update(User $user, TenantBillingSetting $settings): bool
    {
        return $user->can('billing.manage');
    }
}
