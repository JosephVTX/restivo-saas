# Architecture

A **single-domain, single-database multi-tenant platform**: Laravel 13 API +
Inertia/React 19 SPA in one codebase, optimized for low-resource servers via
Octane (FrankenPHP) and Redis.

> AI agents: read `.ai/rules/index.md` first, then only the rule file matching
> the globs you are editing. This document is the map; the rules are the laws.

## Stack

| Layer | Choice |
| --- | --- |
| Backend | PHP 8.3, Laravel 13, Octane (FrankenPHP in prod) |
| Frontend | Inertia, React 19, TypeScript, Vite, Tailwind v4, daisyUI v5 (themes `restivo`/`restivo-dark`), SWR, zod — Spanish UI |
| Data layer | MySQL 8.4, Redis (cache/session/queue) |
| Data fetching | SWR + axios; validation with zod |
| Lint/format | oxlint (JS/TS), Pint (PHP) |
| Tenancy | single DB, `tenant_id` + global scope, spatie teams |
| Access control | spatie/laravel-permission (teams), policies, super admin bypass |
| Querying | spatie/laravel-query-builder (variadic API) |
| AI tooling | Laravel Boost MCP + `.ai/rules` + `docs/` |

## Directory map

```
app/
  Enums/                     TenantStatus, Role
  Http/
    Controllers/
      Admin/                 Inertia pages for super admins
      App/                   Inertia pages for the tenant workspace
      Api/V1/                JSON endpoints consumed by SWR
      Auth/                  session login/register
    Middleware/              HandleInertiaRequests, ResolveTenant, EnsureTenant, EnsureSuperAdmin
    Requests/{Auth,Admin,App}
    Resources/               ProductResource, TenantResource, UserResource, ...
  Models/
    Concerns/{BelongsToTenant,HasUuid}
    Scopes/TenantScope
    Tenant, User, Membership, Product, Role
  Policies/                  ProductPolicy, MembershipPolicy, TenantPolicy
  Providers/                 AppServiceProvider, TenancyServiceProvider
  Services/Tenancy/          TenantProvisioner (roles/permissions), AccessGranter (grant client access)
  Support/Tenancy/           TenantContext, TenantResolver
resources/
  views/app.blade.php        Inertia root (Font Awesome CDN, theme script)
  js/
    app.tsx                  createInertiaApp + SWR provider
    index.css                Tailwind v4 + daisyUI plugin
    components/{layout,ui}    shells, layouts, primitives
    hooks/                   use-shared, use-theme, use-flash, ...
    lib/                     axios, http (fetcher/api), swr, query, utils
    pages/                   Inertia pages (path must match render target)
    schemas/                 zod schemas
    types/                   shared TS types
.ai/rules/                   area-grouped AI rules (index.md)
docs/                        architecture + guides
```

## Request lifecycle (tenant route, e.g. `GET /app/menu/products`)

1. `web` middleware group starts the session and runs `HandleInertiaRequests`.
2. `ResolveTenant` runs in the **`web` middleware group**: it reads the session
   tenant (or the `X-Tenant` header for super admins), binds `TenantContext`,
   sets spatie's team id and clears the user's cached `roles`/`permissions`
   relations. Route middleware then runs `auth` → `tenant` (and `throttle:api`
   on JSON routes).
3. The controller renders the Inertia page with enum/shell props only.
4. The React page mounts and its SWR hook fetches `/api/v1/products` (same
   session), which returns a `ProductResource` collection scoped to the tenant.

Because `SubstituteBindings` runs before `resolve.tenant`, tenant models are
resolved with **explicit scoped queries** in controllers — never implicit
binding (see `.ai/rules/http-layer.md`).

## Data isolation

- Models with `BelongsToTenant` get `TenantScope` (`where tenant_id = context`),
  `tenant_id` auto-fill on create, and `withoutTenantScope()` as the explicit
  cross-tenant escape hatch.
- `TenantResolver` caches tenant lookups in Redis for `tenancy.cache.ttl`.
- `TenantProvisioner` creates owner/admin/member roles and global permissions
  when a tenant is created.

## Onboarding (registration is closed)

There is no public signup. A super admin is the only one who grants access:

1. `POST /api/v1/admin/tenants` — creates the tenant **and** its owner (user +
   membership + `owner` role) through `App\Services\Tenancy\AccessGranter`.
2. `POST /api/v1/admin/tenants/{tenant}/members` — grants access to an existing
   tenant (creates the user if the email is new).

If no password is provided, `AccessGranter` generates one and returns it once as
`temporary_password` so the admin can hand it to the client.

## Routes

| Area | Prefix | Middleware | Purpose |
| --- | --- | --- | --- |
| Central | `/`, `login`, `logout`, `tenant/switch` | guest/auth | login + workspace switching (no signup) |
| Tenant app | `/app/*` | auth, resolve.tenant, tenant | Inertia workspace pages |
| Admin | `/admin/*` | auth, super-admin | Inertia platform pages |
| Tenant API | `/api/v1/*` | auth, resolve.tenant, tenant, throttle:api | JSON for SWR |
| Admin API | `/api/v1/admin/*` | auth, super-admin, throttle:api | JSON for SWR |

## Performance notes (low-resource, high concurrency)

- Octane + FrankenPHP keeps the app booted between requests; `TenantContext` is
  a scoped binding cleared on `RequestTerminated` (no state leaks).
- Redis backs cache, sessions and queues; tenant lookups and permissions are
  cached.
- Query builder endpoints are paginated and capped at 100 rows.
- `Model::preventLazyLoading` is on outside production to catch N+1 early.

## Recipes

- **Add a tenant resource**: follow the 6-step recipe in
  `.ai/rules/http-layer.md`.
- **Add a page**: create the controller (`Inertia::render`) + page file + route
  in the right group; add an SWR hook if it lists/mutates data.
- **Deploy**: see `README.md` and `compose.yaml` (FrankenPHP + MySQL + Redis).
