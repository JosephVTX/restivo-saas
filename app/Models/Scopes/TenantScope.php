<?php

namespace App\Models\Scopes;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filters every query of a tenanted model by the resolved tenant.
 *
 * When no tenant is resolved (central/console context) queries are returned
 * untouched so background jobs and admin tooling can operate across tenants
 * using the withoutTenantScope() escape hatch.
 */
final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->has()) {
            $builder->where($model->qualifyColumn('tenant_id'), $context->id());
        }
    }
}
