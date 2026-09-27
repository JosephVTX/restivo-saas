import { Head } from '@inertiajs/react';
import AdminLayout from '@/components/layout/AdminLayout';
import { PageHeader } from '@/components/ui/PageHeader';

interface Props {
    stats: {
        tenants: number;
        active_tenants: number;
        users: number;
    };
}

export default function AdminDashboard({ stats }: Props) {
    const cards = [
        { label: 'Clientes', value: stats.tenants, icon: 'fa-building', color: 'text-primary' },
        { label: 'Clientes activos', value: stats.active_tenants, icon: 'fa-circle-check', color: 'text-success' },
        { label: 'Usuarios', value: stats.users, icon: 'fa-users', color: 'text-info' },
    ];

    return (
        <AdminLayout title="Resumen de la plataforma">
            <Head title="Administración" />
            <PageHeader title="Resumen de la plataforma" description="Métricas globales de todos los clientes." />

            <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {cards.map((card) => (
                    <div key={card.label} className="card border border-base-300 bg-base-100">
                        <div className="card-body gap-3">
                            <div className="flex items-center justify-between">
                                <span className="text-sm opacity-70">{card.label}</span>
                                <span className={`grid h-9 w-9 place-items-center rounded-lg bg-base-200 ${card.color}`}>
                                    <i className={`fa-solid ${card.icon}`} aria-hidden="true" />
                                </span>
                            </div>
                            <span className="text-3xl font-semibold tabular-nums">{card.value}</span>
                        </div>
                    </div>
                ))}
            </div>
        </AdminLayout>
    );
}
