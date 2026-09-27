# Models, database and tenancy data rules

## Models

- Use PHP attributes on the class, not properties:
  `#[Fillable([...])]`, `#[Hidden([...])]` (see `app/Models/User.php`).
- Casts live in a `protected function casts(): array`.
- Enums are backed string enums in `app/Enums/*` with TitleCase cases
  (`TenantStatus::Active`).
- Tenant-scoped models add `use BelongsToTenant, HasFactory, HasUuid` and a
  `tenant_id` foreign key.
- `HasUuid` sets a uuid on create and makes `uuid` the route key.

## Example resource

`App\Models\Product` is the reference tenant-scoped resource. Copy its shape
(traits, casts, unique slug per tenant) when adding new resources.

## Migrations

- Tenant tables include `foreignId('tenant_id')->constrained()->cascadeOnDelete()`
  and index `['tenant_id', 'status']`.
- Permission tables are already migrated with the teams column `tenant_id`.

## Querying

- Use `spatie/laravel-query-builder` for list endpoints. Note v7 signatures are
  **variadic**: `->allowedFilters(AllowedFilter::partial('name'), ...)` and
  `->allowedSorts('name', 'created_at')` (do not pass arrays).
- Always constrain list queries with pagination (`per_page`, max 100).

## Factories & seeders

- `TenantFactory`, `MembershipFactory`, `ProductFactory`, `UserFactory`
  (`->superAdmin()`, `->forTenant($tenant, 'owner')`).
- Seeders: `RolePermissionSeeder`, `SuperAdminSeeder`, `DemoTenantSeeder`.

## Tests

- PHPUnit (not Pest). Use `RefreshDatabase` and
  `Tests\Concerns\InteractsWithTenancy`.
- Tests run on sqlite in memory with `array` cache
  (`TENANCY_CACHE_STORE=array` in `phpunit.xml`).
