# HTTP layer rules (controllers, requests, resources, routes)

## Two kinds of controllers

1. **Inertia page controllers** (`App\Http\Controllers\App\*`,
   `App\Http\Controllers\Admin\*`) return `Inertia::render('path/Page', [...])`
   with only the props the page shell needs (enums, small stats). They do not
   return lists.
2. **JSON API controllers** (`App\Http\Controllers\Api\V1\*`) return resources
   or JSON. These are what the React app consumes with SWR via `/api/v1/*`.

Shared (non-page) routes live in `routes/web.php` (session + CSRF). There is no
stateless `routes/api.php`; the `/api/v1` routes are also in `web.php` with the
`api` rate limiter so the SPA reuses cookies/CSRF.

## Reuse first — do NOT recreate

- **JSON controllers MUST extend `App\Http\Controllers\Api\V1\ApiController`**
  and use its helpers instead of rewriting plumbing:
  - `perPage($request)` — clamped page size.
  - `indexQuery(Model::class, $request, filters: [...], sorts: [...], withCount: [...])`
    — spatie/query-builder + pagination + `withQueryString`.
  - `findByUuid(Model::class, $uuid)` — tenant-scoped lookup (never implicit
    binding for tenant models).
  - `searchFilter(['name', 'email'])` — the shared `filter[search]` callback
    across columns; never hand-write the search closure again.
- **Inertia enum props:** pass `enum_options(Enum::class)` (helper in
  `app/Support/helpers.php`) for the `[{value, label}]` shape — do not
  `array_map` over `::cases()` in every page controller.
- Validation via Form Requests, output via Resources, authorization via
  Policies — do not inline these.
- Tenant roles/permissions: `TenantProvisioner`. Client access: `AccessGranter`
  (never re-implement user+membership+role creation — `Api\V1\MemberController`,
  `Api\V1\Admin\TenantController` and the admin panel all delegate to it).
- Tenant-scoped queries on the `Role` model use the `Role::forCurrentTenant()`
  scope. Escape a tenant global scope with `Model::withoutTenantScope()`.
- Do not add a JSON endpoint that merely duplicates an Inertia page's props
  (dashboards are rendered server-side); keep the API surface intentional.

## Binding tenant models — IMPORTANT

`SubstituteBindings` runs before `resolve.tenant`, so implicit route-model
binding would resolve a tenant model **without** the tenant scope. Do NOT
type-hint tenant models in controller signatures. Instead accept the uuid and
query with the scope already applied:

```php
public function update(UpdateProjectRequest $request, string $project): ProjectResource
{
    $model = Project::query()->where('uuid', $project)->firstOrFail(); // scoped
    ...
}
```

Non-tenanted models (`Tenant` in admin routes) may use implicit binding since
they have no global scope.

## Validation

- Form requests in `app/Http/Requests/{Admin,App,Auth}`. `authorize()` checks a
  policy/permission (e.g. `$this->user()->can('projects.update', $project)`).
- Return Laravel's 422 JSON; the frontend maps it with `validationErrors()`.

## Resources

- `app/Http/Resources/*Resource` shape API output. Include labels for enums and
  use `whenLoaded`/`whenCounted` for optional relations.

## Index/query contract (spatie query-builder)

List endpoints use spatie/laravel-query-builder. The SPA sends params in the
spatie form:

```
?page=1&per_page=15&sort=-created_at&filter[search]=foo&filter[status]=active
```

- Declare a matching filter per accepted key in `allowedFilters(...)`. Use
  `AllowedFilter::exact('status')` for enums and `$this->searchFilter(['col1',
  'col2'])` for free-text search.
- A bare `?search=` / `?status=` is **ignored** by spatie — it must be
  `filter[key]`. This is the classic "search does nothing" bug.
- The frontend builds this with `lib/query.ts`:
  `buildQuery({ page, filter: { search, status } })`.

## Routes (see `routes/web.php`)

- Central: `/`, `login`, `logout`, `tenant/switch` (no public registration).
- Tenant app (Inertia): `/app/*` behind `['auth','resolve.tenant','tenant']`.
- Admin (Inertia): `/admin/*` behind `['auth','super-admin']`.
- JSON: `/api/v1/*` (tenant) and `/api/v1/admin/*` (super admin).

Route names: `app.*`, `admin.*`, `api.*`, `api.admin.*`.

## Access provisioning

Super admins create tenants and grant access via
`Api\V1\Admin\TenantController@store` (tenant + owner) and `@grantAccess`
(existing tenant). Both delegate to `App\Services\Tenancy\AccessGranter`.
Never add public registration endpoints.

## Adding a new tenant resource (recipe)

1. Migration with `tenant_id`; model with `BelongsToTenant, HasUuid`.
2. `StoreXRequest` / `UpdateXRequest` + `XPolicy` (permission names).
3. `Api\V1\XController` (index/store/show/update/destroy) using `indexQuery` +
   `searchFilter`/`findByUuid`.
4. `App\XController` returning the Inertia page (`enum_options(Enum::class)`).
5. Page under `resources/js/pages/app/X/Index.tsx` using the generic
   `useResource` + `useCrud` (no per-resource hook) and a zod schema.
6. Routes in `web.php` (Inertia group + `api/v1` group) and a feature test.
