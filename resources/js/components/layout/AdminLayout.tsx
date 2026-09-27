import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AppShell, type NavItem } from '@/components/layout/AppShell';

export default function AdminLayout({
    title,
    subtitle,
    children,
}: {
    title: string;
    subtitle?: string;
    children: ReactNode;
}) {
    const { url } = usePage();

    const nav: NavItem[] = [
        { href: '/admin', label: 'Resumen', icon: 'fa-gauge-high', active: url === '/admin' },
        { href: '/admin/tenants', label: 'Clientes', icon: 'fa-building', active: url.startsWith('/admin/tenants') },
        { href: '/admin/users', label: 'Usuarios', icon: 'fa-users', active: url.startsWith('/admin/users') },
        {
            href: '/admin/settings/integrations',
            label: 'Integraciones',
            icon: 'fa-cloud',
            active: url.startsWith('/admin/settings/integrations'),
        },
    ];

    return (
        <AppShell variant="admin" title={title} subtitle={subtitle} nav={nav}>
            {children}
        </AppShell>
    );
}
