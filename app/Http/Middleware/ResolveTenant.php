<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active tenant for the request and binds it to the TenantContext
 * and to spatie/laravel-permission's team id.
 *
 * Single-domain strategy: the tenant comes from the session (set at login or
 * via the tenant switcher). Super admins may also pass the X-Tenant header or
 * impersonate through the session.
 */
final class ResolveTenant
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly TenantResolver $resolver,
        private readonly PermissionRegistrar $permissions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = $this->resolve($request);

        $this->context->set($tenant);
        $this->permissions->setPermissionsTeamId($tenant?->getKey());

        // Shared props / earlier middleware may have loaded the roles and
        // permissions relations while the team id was still null; drop them so
        // permission checks re-query for the resolved tenant.
        if ($user !== null) {
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }

        if ($tenant !== null) {
            Log::withContext(['tenant_id' => $tenant->getKey()]);
        }

        return $next($request);
    }

    private function resolve(Request $request): ?Tenant
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $sessionKey = (string) config('tenancy.session_key', 'tenant_id');
        $sessionTenantId = $request->session()->get($sessionKey);

        if ($user->isSuperAdmin()) {
            $header = (string) config('tenancy.header', 'X-Tenant');

            if ($request->hasHeader($header)) {
                return $this->resolver->find((string) $request->header($header));
            }

            return $sessionTenantId ? $this->resolver->findById($sessionTenantId) : null;
        }

        $tenantId = $sessionTenantId ?? $user->defaultTenantId();

        if ($tenantId === null) {
            return null;
        }

        if (! $user->belongsToTenant($tenantId)) {
            $request->session()->forget($sessionKey);

            return null;
        }

        return $this->resolver->findById($tenantId);
    }
}
