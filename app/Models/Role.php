<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * App-owned role model (lets us add relations/resources without touching the
 * vendor package). Registered in config/permission.php -> models.role.
 */
class Role extends SpatieRole
{
    /**
     * Scope roles to the tenant resolved for the current request. Roles are
     * stored per tenant through spatie's teams feature (team_foreign_key).
     */
    public function scopeForCurrentTenant(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('tenant_id'), tenant_context()->id());
    }
}
