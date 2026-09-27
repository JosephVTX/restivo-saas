# Frontend rules (Inertia + React + SWR + zod + daisyUI)

## Stack

React 19 + TypeScript strict, Vite, Inertia, **SWR** for data, **axios** for
mutations, **zod** for client validation, Tailwind v4 + daisyUI v5, **oxlint**
(never ESLint/Prettier). Package manager: **pnpm** only.

- Path alias `@/*` → `resources/js/*` (in `tsconfig.json` and `vite.config.ts`).
- Root view: `resources/views/app.blade.php`; entry: `resources/js/app.tsx`.
- Styles: `resources/js/index.css` (Tailwind + daisyUI plugin).

## Data fetching pattern

- Inertia renders the page shell and shared props (`auth`, `tenant`, `tenants`,
  `flash`, `app`) — see `app/Http/Middleware/HandleInertiaRequests.php`.
- Lists/data come from `useResource<T>(endpoint, params)` (`hooks/use-resource.ts`)
  hitting `/api/v1/*`. The global fetcher is `lib/http.ts` (`fetcher`). There are
  **no per-resource list hooks**: call `useResource<Project>('/api/v1/projects',
  { page, filter: { search, status } })` and use the returned `items`.
- Mutations use `api.post/patch/delete` then `mutate()` to revalidate, and
  `validationErrors(error)` to map 422 responses onto form fields.
- Client-side validation uses zod schemas in `resources/js/schemas/*`.
- **Search inputs MUST debounce** via `useDebouncedSearch()` (350 ms default):
  bind the input to `search`/`change` and use the debounced `query` in the SWR
  key. Never refetch on every keystroke. See `pages/admin/Users.tsx` for the
  reference implementation.

## Reuse first — do NOT recreate

Before writing a table, modal, form field, search box or CRUD flow, use the
existing generic pieces. Only add a new generic under `components/ui` or
`hooks` when it will be reused by more than one page.

- **Forms:** `components/ui/Field` (daisyUI v5 fieldset) + `Modal` (+
  `confirmDelete`). Auth/settings/profile use Inertia `useForm`.
- **List pages:** `TableShell` (container + loading + empty + head),
  `Pagination`, `SearchInput`, `StatusFilter`, `RowActions`, `StatusBadge`,
  `EmptyState`, `PageHeader`.
- **CRUD:** `hooks/use-crud` handles modal open/edit/values/errors/saving and
  create/update/delete + SWR revalidation (pass `endpoint`, `schema`, `empty`,
  `toValues`, `mutate`, `removeLabel`). Combined with `useResource` it is
  the whole pattern. Reference: `pages/app/Projects/Index.tsx`.
- **Search:** `hooks/use-debounced-search` (never refetch per keystroke).
- **Data access:** `lib/http` (`api`, `validationErrors`, `fetcher`),
  `lib/query` (`buildQuery`), and `hooks/use-resource` (generic SWR list). Map
  UI params to the spatie contract via `{ page, filter: { search, status } }` →
  `filter[search]=…`; never send a bare `search=`.
- **Layout:** `components/layout/{AppLayout,AdminLayout,AuthLayout}` +
  `AppShell`, `TenantSwitcher`, `ThemeToggle`, `FlashToasts`.

Adding a new tenant resource should mostly be: reuse `useResource` + `useCrud` in
one page wiring the pieces above + migrations/model/policy/routes. See the recipe
in `http-layer.md`.

## Components & pages

- Layouts: `components/layout/AppLayout` (tenant) and `AdminLayout` (platform),
  both rendering `AppShell`. Auth pages use `AuthLayout`.
- UI primitives in `components/ui/*` (Modal, Pagination, StatusBadge, ...).
- Pages in `resources/js/pages/**` must match the Inertia page path exactly
  (e.g. `Inertia::render('app/Projects/Index')` → `pages/app/Projects/Index.tsx`).
- Auth pages: only `pages/auth/Login.tsx` (registration is closed). The admin
  tenant screen (`pages/admin/Tenants.tsx`) creates tenants and grants access.
- Use daisyUI semantic classes (`btn`, `card`, `badge`, `input`) and Font
  Awesome icons via `<i className="fa-solid fa-x" />` (loaded from CDN in the
  blade root view).

## daisyUI v5 forms (IMPORTANT)

daisyUI v5 **removed** `form-control`, `label-text` and `input-bordered`. Do not
reintroduce them — inputs will look broken/misaligned. Build every field with
`fieldset` + `fieldset-legend` + `label`, or the shared `components/ui/Field`:

```tsx
<Field label="Correo electrónico" error={errors.email}>
    <input className="input w-full" value={...} onChange={...} />
</Field>
```

- Inputs/selects/textareas are bordered by default: use `input`, `select`,
  `textarea` (no `-bordered`).
- Input with a leading icon uses the label wrapper:
  `<label className="input input-sm"><i className="fa-solid fa-magnifying-glass" /><input /></label>`.
- Modals: `components/ui/Modal` (with `confirmDelete`). Tables: `table` inside a
  `rounded-box border border-base-300 bg-base-100` container.

## daisyUI v5 menu / sidebar (IMPORTANT)

- `.menu` ships `width: fit-content` in v5 — add **`w-full`** to the `<ul>` or the
  sidebar menu won't fill its panel.
- The active menu item class is **`menu-active`** (the old `active` does nothing
  in v5). daisyUI also styles `[aria-current="page"]`. See `AppShell`:
  `<Link className={cn('font-medium', item.active && 'menu-active')}
  aria-current={item.active ? 'page' : undefined}>`.
- Nav items carry an `active` flag computed in `AppLayout`/`AdminLayout` from
  `usePage().url` (`===` for index routes, `startsWith` for sections).

## Theme & language

- Custom daisyUI themes `restivo` (light, default) and `restivo-dark` (prefersdark)
  live in `resources/js/index.css` (`@plugin "daisyui/theme"`). Toggle with
  `useTheme()` which writes `data-theme`.
- **All UI copy is Spanish (es).** Keep new strings in Spanish. The backend
  locale is `es` (`lang/es/validation.php`, `auth.php`, ...) and enum labels
  (`TenantStatus`, `ProjectStatus`, `Role`) return Spanish.
- Role **names** and permission **names** stay in English as technical
  identifiers (`owner`/`admin`/`member`, `projects.view`). Their visible labels
  come from `lang/es/roles.php` + `lang/es/permissions.php` (exposed by
  `RoleResource`) and `lib/labels.ts` `roleLabel()` for inline role names.
  **Never render the raw role/permission identifier.**
- Status **values** (`draft`/`active`/`archived`/`trial`/`suspended`/`cancelled`)
  are also stored as English identifiers. In the UI use the shared
  `StatusBadge` — it already falls back to `lib/labels.ts` `statusLabel()`, so
  passing `<StatusBadge status={x} />` renders Spanish. Prefer the server
  `status_label` when the API sends it. **Never print a raw status value.**
- Dates go through `lib/utils.ts` `formatDate()` (defaults to `es` locale).
- Regional defaults: locale `es`, timezone `America/Lima`, currency `PEN`
  (oriented to Lima, Peru). New tenants default to `locale = 'es'` (model
  `$attributes` + migration default); the super-admin tenant form defaults to
  `es` too.

## Commands

- `pnpm dev` / `pnpm build` (`tsc --noEmit && vite build`).
- `pnpm lint` / `pnpm lint:fix` (oxlint). Run lint + build after changes.
