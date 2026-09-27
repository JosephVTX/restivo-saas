import { Link, router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { TenantSwitcher } from '@/components/layout/TenantSwitcher';
import { ExpiryNotice } from '@/components/ui/ExpiryNotice';
import { FlashToasts } from '@/components/ui/FlashToasts';
import { ThemeToggle } from '@/components/ui/ThemeToggle';
import { useShared } from '@/hooks/use-shared';
import { roleLabel } from '@/lib/labels';
import { cn } from '@/lib/utils';

export interface NavItem {
    href: string;
    label: string;
    icon: string;
    active: boolean;
}

function logout() {
    router.post('/logout');
}

export function AppShell({
    variant,
    title,
    subtitle,
    nav,
    children,
}: {
    variant: 'app' | 'admin';
    title: string;
    subtitle?: string;
    nav: NavItem[];
    children: ReactNode;
}) {
    const { auth, tenant, app } = useShared();

    return (
        <div className="drawer lg:drawer-open">
            <input id="main-drawer" type="checkbox" className="drawer-toggle" />
            <FlashToasts />

            <div className="drawer-content flex min-h-screen flex-col">
                <header className="navbar sticky top-0 z-30 min-h-16 border-b border-base-300 bg-base-100/80 px-4 backdrop-blur">
                    <div className="flex-none lg:hidden">
                        <label htmlFor="main-drawer" className="btn btn-square btn-ghost btn-sm">
                            <i className="fa-solid fa-bars" aria-hidden="true" />
                        </label>
                    </div>

                    <div className="flex-1 px-2">
                        <h1 className="text-base font-semibold leading-tight">{title}</h1>
                        {subtitle ? <p className="text-xs opacity-60">{subtitle}</p> : null}
                    </div>

                    <div className="flex flex-none items-center gap-1">
                        {variant === 'app' ? <TenantSwitcher /> : null}
                        {variant === 'admin' && tenant ? (
                            <span className="badge badge-ghost badge-sm hidden sm:inline-flex">
                                <i className="fa-solid fa-eye mr-1" aria-hidden="true" />
                                {tenant.name}
                            </span>
                        ) : null}
                        <ThemeToggle />
                        <div className="dropdown dropdown-end">
                            <button type="button" tabIndex={0} className="btn btn-ghost btn-sm gap-2">
                                <span className="grid h-6 w-6 place-items-center rounded-full bg-primary text-xs font-semibold text-primary-content">
                                    {auth.user?.name?.charAt(0).toUpperCase()}
                                </span>
                                <span className="hidden max-w-32 truncate sm:inline">{auth.user?.name}</span>
                                <i className="fa-solid fa-chevron-down text-[10px] opacity-60" aria-hidden="true" />
                            </button>
                            <ul
                                tabIndex={0}
                                className="dropdown-content menu z-40 mt-2 w-60 rounded-box bg-base-100 p-2 shadow-lg"
                            >
                                <li className="menu-title text-xs">
                                    {auth.user?.email}
                                </li>
                                {variant === 'app' ? (
                                    <li>
                                        <Link href="/app/profile" prefetch>
                                            <i className="fa-solid fa-id-card" aria-hidden="true" /> Mi perfil
                                        </Link>
                                    </li>
                                ) : null}
                                {variant === 'app' && auth.user?.is_super_admin ? (
                                    <li>
                                        <Link href="/admin" prefetch>
                                            <i className="fa-solid fa-shuffle" aria-hidden="true" /> Panel de administración
                                        </Link>
                                    </li>
                                ) : null}
                                <li>
                                    <button type="button" onClick={logout}>
                                        <i className="fa-solid fa-right-from-bracket" aria-hidden="true" /> Cerrar sesión
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-7xl flex-1 p-4 lg:p-8">
                    {variant === 'app' && auth.user?.is_super_admin && tenant ? (
                        <div
                            role="alert"
                            className="alert mb-4 items-center gap-3 rounded-box border border-info/40 bg-info/10 py-2 text-sm"
                        >
                            <i className="fa-solid fa-user-shield" aria-hidden="true" />
                            <span className="flex-1">
                                Estás viendo <strong>{tenant.name}</strong> como super administrador.
                            </span>
                            <button
                                type="button"
                                className="btn btn-ghost btn-xs"
                                onClick={() => router.post('/admin/leave')}
                            >
                                Salir
                            </button>
                        </div>
                    ) : null}
                    {variant === 'app' ? <ExpiryNotice /> : null}
                    {children}
                </main>

                <footer className="mx-auto w-full max-w-7xl px-6 py-4 text-xs opacity-50">
                    {app.name} · {variant === 'admin' ? 'Panel de plataforma' : 'Espacio de trabajo'}
                </footer>
            </div>

            <div className="drawer-side z-40">
                <label htmlFor="main-drawer" className="drawer-overlay" aria-label="Cerrar menú" />
                <aside className="flex min-h-full w-72 flex-col border-r border-base-300 bg-base-100">
                    <div className="flex items-center gap-3 border-b border-base-300 px-5 py-4">
                        <span className="grid h-9 w-9 place-items-center rounded-xl bg-primary text-primary-content">
                            <i className="fa-solid fa-layer-group" aria-hidden="true" />
                        </span>
                        <div className="min-w-0">
                            <p className="truncate font-semibold leading-tight">
                                {variant === 'admin' ? 'Plataforma' : tenant?.name ?? 'Espacio'}
                            </p>
                            <p className="truncate text-xs opacity-60">{app.name}</p>
                        </div>
                    </div>

                    <ul className="menu w-full flex-1 gap-1 p-3">
                        {nav.map((item) => (
                            <li key={item.href}>
                                <Link
                                    href={item.href}
                                    prefetch
                                    aria-current={item.active ? 'page' : undefined}
                                    className={cn('font-medium', item.active && 'menu-active')}
                                >
                                    <i className={`fa-solid ${item.icon} w-5 text-center`} aria-hidden="true" />
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                    </ul>

                    <div className="border-t border-base-300 p-4">
                        <div className="flex items-center gap-3 rounded-box bg-base-200 p-3">
                            <span className="grid h-8 w-8 place-items-center rounded-full bg-primary text-sm font-semibold text-primary-content">
                                {auth.user?.name?.charAt(0).toUpperCase()}
                            </span>
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium">{auth.user?.name}</p>
                                <p className="truncate text-xs opacity-60">
                                    {auth.user?.is_super_admin ? 'Super administrador' : roleLabel(auth.roles?.[0])}
                                </p>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    );
}
