---
name: daisyui-v5
description: Use when building or editing UI in resources/js (forms, inputs, modals, tables, theme). Project-specific daisyUI v5 rules — prevents the broken form-control patterns.
---

# daisyUI v5 in this project

Stack: Tailwind CSS v4 (CSS-first, no `tailwind.config.js`) + daisyUI v5,
configured in `resources/js/index.css`. Linter: oxlint. React 19 + TypeScript.

## CRITICAL: forms

daisyUI v5 **removed** `form-control`, `label-text`, `input-bordered`,
`select-bordered`, `textarea-bordered`. Using them breaks alignment (inputs
collapse / labels float). Always use `fieldset` + `fieldset-legend` + `label`,
or the shared component:

```tsx
import { Field } from '@/components/ui/Field';

<Field label="Correo electrónico" error={errors.email} hint="Opcional">
    <input className="input w-full" value={value} onChange={onChange} />
</Field>
```

- Inputs/selects/textareas are bordered by default: `input`, `select`, `textarea`.
- Input with a leading icon:
  `<label className="input input-sm"><i className="fa-solid fa-magnifying-glass" /><input /></label>`.
- Select with options uses `className="select w-full"` (not `select-bordered`).
- Checkbox/toggle: `<input className="checkbox checkbox-sm" />` inside a flex label.

## Building blocks used here

- `components/ui/Field` (form), `Modal` + `confirmDelete` (dialogs),
  `Pagination`, `StatusBadge`, `EmptyState`, `PageHeader`, `ThemeToggle`,
  `FlashToasts`.
- Layouts: `components/layout/AppLayout` / `AdminLayout` (render `AppShell`) and
  `AuthLayout`. Sidebar + header + tenant switcher live in `AppShell`.
- Tables: `<div className="overflow-x-auto rounded-box border border-base-300 bg-base-100"><table className="table">`.
- Cards: `card border border-base-300 bg-base-100`.

## Theme

Custom themes `restivo` (light, default) and `restivo-dark` (prefersdark) defined with
`@plugin "daisyui/theme"` in `resources/js/index.css`. Toggle with `useTheme()`
(sets `data-theme`). Use semantic colors (`bg-base-200`, `text-primary`,
`badge-success`) — never raw hex.

## Language

All UI copy is **Spanish (es)**. Icons via Font Awesome CDN:
`<i className="fa-solid fa-x" aria-hidden="true" />`.

## Verify

Run `pnpm lint` and `pnpm build` after changes.
