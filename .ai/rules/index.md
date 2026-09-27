# Project AI rules index

Area-grouped rules for this monolith. Read the rule whose globs match the files
you are about to touch. Keep answers short and edit only what is necessary.

| Globs | Rule file | Covers |
| --- | --- | --- |
| `app/Models/**`, `app/Enums/**`, `app/Models/Scopes/**`, `database/migrations/**`, `database/factories/**`, `app/Services/Tenancy/**`, `app/Support/Tenancy/**` | `database.md` | Models, tenancy scoping, migrations, factories, enums |
| `app/Models/Tenant.php`, `app/Support/Tenancy/**`, `app/Http/Middleware/ResolveTenant.php`, `config/tenancy.php`, `app/Providers/TenancyServiceProvider.php` | `tenancy.md` | Multi-tenant invariants |
| `app/Http/Controllers/**`, `app/Http/Requests/**`, `app/Http/Resources/**`, `app/Policies/**`, `routes/**` | `http-layer.md` | Controllers, validation, resources, routing |
| `resources/js/**` | `frontend.md` | Inertia + React + SWR + zod + daisyUI |

## Golden rules

1. Tenant-scoped models MUST use `BelongsToTenant`. Never query them without
   the resolved tenant unless you use `withoutTenantScope()` on purpose.
2. Never rely on implicit route-model binding for tenant models — resolve them
   with a tenant-scoped query in the controller (see `http-layer.md`).
3. The frontend never fetches "everything": use SWR hooks against `/api/v1/*`.
4. After PHP edits run `vendor/bin/pint --dirty --format agent`. After JS/TS
   edits run `pnpm lint` and `pnpm build`.
5. **Reuse before you recreate.** The project ships generic building blocks
   (frontend UI kit + `useCrud`/`useDebouncedSearch`; backend `ApiController`,
   resources, policies, `AccessGranter`). Prefer them over new bespoke code; if
   nothing fits, extract a new generic and document it here.
6. **The whole UI is Spanish.** Never render raw English identifiers: statuses go
   through `StatusBadge`/`statusLabel()`, roles through `roleLabel()` or the
   server `*_label`, permissions through `RoleResource` labels. Store identifiers
   in English, show labels in Spanish. Dates use `formatDate()` (default `es`).
