import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AppShell, type NavItem } from '@/components/layout/AppShell';
import { useCan } from '@/hooks/use-can';

export default function AppLayout({
    title,
    subtitle,
    children,
}: {
    title: string;
    subtitle?: string;
    children: ReactNode;
}) {
    const { url } = usePage();
    const can = useCan();

    const path = url.split('?')[0];
    const matches = (href: string) => path === href || path.startsWith(`${href}/`);

    const items: (NavItem & { permission?: string })[] = [
        { href: '/app', label: 'Panel', icon: 'fa-gauge-high', active: path === '/app' },
        {
            href: '/app/tables',
            label: 'Mesas',
            icon: 'fa-chair',
            active: matches('/app/tables'),
            permission: 'tables.view',
        },
        {
            href: '/app/pos',
            label: 'POS',
            icon: 'fa-cash-register',
            active: matches('/app/pos'),
            permission: 'orders.create',
        },
        {
            href: '/app/orders',
            label: 'Pedidos',
            icon: 'fa-receipt',
            active: matches('/app/orders'),
            permission: 'orders.view',
        },
        {
            href: '/app/cash',
            label: 'Caja',
            icon: 'fa-cash-register',
            active: matches('/app/cash'),
            permission: 'cash.view',
        },
        {
            href: '/app/documents',
            label: 'Comprobantes',
            icon: 'fa-file-invoice',
            active: matches('/app/documents'),
            permission: 'documents.view',
        },
        {
            href: '/app/customers',
            label: 'Clientes',
            icon: 'fa-users',
            active: matches('/app/customers'),
            permission: 'customers.view',
        },
        {
            href: '/app/kitchen',
            label: 'Cocina',
            icon: 'fa-fire-burner',
            active: matches('/app/kitchen'),
            permission: 'kitchen.view',
        },
        {
            href: '/app/zones',
            label: 'Zonas',
            icon: 'fa-map',
            active: matches('/app/zones'),
            permission: 'tables.manage',
        },
        {
            href: '/app/menu/products',
            label: 'Carta',
            icon: 'fa-book-open',
            active: matches('/app/menu/products'),
            permission: 'menu.view',
        },
        {
            href: '/app/menu/categories',
            label: 'Categorías',
            icon: 'fa-tags',
            active: matches('/app/menu/categories'),
            permission: 'menu.view',
        },
        {
            href: '/app/modifiers',
            label: 'Opciones',
            icon: 'fa-list-check',
            active: matches('/app/modifiers'),
            permission: 'menu.view',
        },
        {
            href: '/app/members',
            label: 'Miembros',
            icon: 'fa-users',
            active: matches('/app/members'),
            permission: 'members.view',
        },
        {
            href: '/app/roles',
            label: 'Roles',
            icon: 'fa-user-shield',
            active: matches('/app/roles'),
            permission: 'roles.view',
        },
        {
            href: '/app/settings/billing',
            label: 'Facturación',
            icon: 'fa-file-invoice-dollar',
            active: matches('/app/settings/billing'),
            permission: 'billing.manage',
        },
        {
            href: '/app/settings',
            label: 'Configuración',
            icon: 'fa-gear',
            active: path === '/app/settings',
            permission: 'settings.view',
        },
    ];

    const nav: NavItem[] = items.filter((item) => !item.permission || can(item.permission));

    return (
        <AppShell variant="app" title={title} subtitle={subtitle} nav={nav}>
            {children}
        </AppShell>
    );
}
