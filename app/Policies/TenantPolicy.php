<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->belongsToTenant($tenant->getKey());
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->belongsToTenant($tenant->getKey()) && $user->can('settings.manage');
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->belongsToTenant($tenant->getKey()) && $user->can('settings.manage');
    }
}
