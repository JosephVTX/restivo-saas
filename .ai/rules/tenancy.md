# Multi-tenancy invariants

This app is **single-database** multi-tenant on a **single domain** (no
wildcard subdomains). Data is isolated by a `tenant_id` column and a global
scope. Do not introduce per-tenant connections or subdomain resolution.

## Core pieces

- `app/Support/Tenancy/TenantContext` — scoped singleton holding the resolved
  `Tenant` for the current request. `has()`, `id()`, `require()`, `tenant()`.
- `app/Support/Tenancy/TenantResolver` — resolves a tenant by id/uuid/slug with
  a Redis cache. Bound as a singleton using `config('tenancy.cache.store')`.
- `app/Models/Scopes/TenantScope` — global scope: when a tenant is resolved,
  adds `where(tenant_id = context->id())`.
- `app/Models/Concerns/BelongsToTenant` — adds the scope and auto-fills
  `tenant_id` on create. Escape hatch: `Model::withoutTenantScope()`.
- `app/Http/Middleware/ResolveTenant` — resolves the tenant and calls
  `setPermissionsTeamId()` before the controller runs. Alias: `resolve.tenant`.
- `app/Http/Middleware/EnsureTenant` — aborts when there is no active/usable
  tenant. Alias: `tenant`.
- `app/Services/Tenancy/TenantProvisioner` — creates a tenant's roles
  (owner/admin/member) and syncs global permissions. Triggered from
  `Tenant::created`.

## Resolution rules

- Active tenant is stored in the session under
  `config('tenancy.session_key')` (`tenant_id`).
- Normal users: session tenant, falling back to their first membership. The
  user must be a member (`belongsToTenant`).
- Super admins (`users.is_super_admin`): may send the `X-Tenant` header (api)
  or impersonate via the session; otherwise they run in "central" context with
  no tenant.

`ResolveTenant` lives in the **web middleware group** (after the session starts),
so the tenant and the permission team id are ready before route middleware like
`throttle:*` and before authorization. After `setPermissionsTeamId()` it calls
`unsetRelation('roles')` and `unsetRelation('permissions')` on the user: shared
props or earlier middleware can load those relations while the team id is still
`null`, and spatie caches them — silently making `can()` fail. Keep that reset.

The `api` rate limiter is keyed by the authenticated user (tenant-tier budget)
or IP, because Laravel runs `throttle` before unprioritised middleware resolves
the tenant.

## Roles / permissions

- spatie/laravel-permission uses **teams** with `tenant_id` as the team key
  (`config('permission.team_foreign_key')`).
- Permissions are global; roles are per-tenant. Always ensure the team id is
  set (`setPermissionsTeamId($tenant->id)`) before assigning or checking roles.
- Super admins bypass every policy via the `Gate::before` hook in
  `TenancyServiceProvider`.

## Access provisioning (registration is CLOSED)

There is **no public registration**. A client only gets a workspace when a
**super admin** grants access:

- `POST /api/v1/admin/tenants` creates the tenant **and** its owner user +
  membership + `owner` role in one call (`App\Services\Tenancy\AccessGranter`).
- `POST /api/v1/admin/tenants/{tenant}/members` grants access to an existing
  tenant (creates the user if needed).
- `AccessGranter::grant()` is the single source of truth for this flow. If a
  password is not supplied, it generates one and returns it once as
  `temporary_password` — never store or log it.
- Do not reintroduce `GET/POST /register` or any self-serve signup.

## Octane

`TenantContext` is a `scoped` binding and is cleared on `RequestTerminated`.
Never store tenant state in singletons or static properties.
