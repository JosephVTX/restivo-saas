import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import useSWR from 'swr';
import AppLayout from '@/components/layout/AppLayout';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { api, fetcher } from '@/lib/http';
import { cn } from '@/lib/utils';
import type { KitchenItem, OrderItemStatus } from '@/types';

type StationFilter = 'all' | 'kitchen' | 'bar';

interface Column {
    status: OrderItemStatus;
    title: string;
    accent: string;
}

const columns: Column[] = [
    { status: 'pending', title: 'Pendiente', accent: 'border-warning' },
    { status: 'preparing', title: 'En preparación', accent: 'border-info' },
    { status: 'ready', title: 'Listo', accent: 'border-success' },
];

const nextAction: Record<string, { status: OrderItemStatus; label: string; className: string; icon: string }> = {
    pending: { status: 'preparing', label: 'Empezar', className: 'btn-info', icon: 'fa-play' },
    preparing: { status: 'ready', label: 'Listo', className: 'btn-success', icon: 'fa-bell' },
    ready: { status: 'delivered', label: 'Entregado', className: 'btn-primary', icon: 'fa-check' },
};

const stationFilters: { value: StationFilter; label: string; icon: string }[] = [
    { value: 'all', label: 'Todos', icon: 'fa-layer-group' },
    { value: 'kitchen', label: 'Cocina', icon: 'fa-fire-burner' },
    { value: 'bar', label: 'Barra', icon: 'fa-martini-glass-citrus' },
];

function waitingClass(minutes: number): string {
    if (minutes >= 15) {
        return 'text-error';
    }

    if (minutes >= 10) {
        return 'text-warning';
    }

    return 'opacity-60';
}

export default function KitchenIndex() {
    const [station, setStation] = useState<StationFilter>('all');
    const [busy, setBusy] = useState<string | null>(null);

    const key = station === 'all' ? '/api/v1/kitchen' : `/api/v1/kitchen?filter[station]=${station}`;

    const { data, mutate } = useSWR<{ data: KitchenItem[] }>(key, fetcher, {
        refreshInterval: 10000,
        revalidateOnFocus: true,
    });

    const items = useMemo(() => data?.data ?? [], [data]);

    const grouped = useMemo(() => {
        const map: Record<OrderItemStatus, KitchenItem[]> = {
            pending: [],
            preparing: [],
            ready: [],
            delivered: [],
            void: [],
        };

        for (const item of items) {
            map[item.status].push(item);
        }

        return map;
    }, [items]);

    const advance = async (item: KitchenItem) => {
        const action = nextAction[item.status];

        if (!action) {
            return;
        }

        setBusy(item.uuid);

        try {
            await api.patch(`/api/v1/order-items/${item.uuid}/status`, { status: action.status });
            await mutate();
        } finally {
            setBusy(null);
        }
    };

    return (
        <AppLayout title="Cocina" subtitle="Comandas por preparar">
            <Head title="Cocina" />
            <PageHeader
                title="Cocina"
                description="Avanza las comandas a medida que se preparan."
                actions={
                    <div className="join">
                        {stationFilters.map((filter) => (
                            <button
                                key={filter.value}
                                type="button"
                                className={cn('btn btn-sm join-item', station === filter.value && 'btn-primary')}
                                onClick={() => setStation(filter.value)}
                            >
                                <i className={`fa-solid ${filter.icon}`} aria-hidden="true" /> {filter.label}
                            </button>
                        ))}
                    </div>
                }
            />

            <div className="mt-6 grid gap-4 lg:grid-cols-3">
                {columns.map((column) => {
                    const list = grouped[column.status];

                    return (
                        <section
                            key={column.status}
                            className={cn('flex flex-col rounded-box border-t-4 bg-base-200/40', column.accent)}
                        >
                            <header className="flex items-center justify-between gap-2 px-4 py-3">
                                <h2 className="text-lg font-semibold">{column.title}</h2>
                                <span className="badge badge-lg">{list.length}</span>
                            </header>

                            <div className="flex flex-1 flex-col gap-3 px-3 pb-3">
                                {list.length === 0 ? (
                                    <p className="py-10 text-center text-sm opacity-60">Sin comandas</p>
                                ) : (
                                    list.map((item) => {
                                        const action = nextAction[item.status];
                                        const heading = item.table_name
                                            ? `${item.table_name} · Pedido #${item.order_number}`
                                            : `${item.order_type_label} · Pedido #${item.order_number}`;

                                        return (
                                            <article
                                                key={item.uuid}
                                                className="card border border-base-300 bg-base-100 shadow-sm"
                                            >
                                                <div className="card-body gap-3 p-4">
                                                    <div className="flex items-start justify-between gap-2">
                                                        <div className="min-w-0">
                                                            <p className="text-lg font-bold leading-tight">{heading}</p>
                                                            <p className="text-xs opacity-70">
                                                                {item.station_label}
                                                                {item.waiter_name ? ` · ${item.waiter_name}` : ''}
                                                            </p>
                                                        </div>
                                                        <StatusBadge status={item.status} label={item.status_label} />
                                                    </div>

                                                    <p className="text-xl font-semibold leading-tight">
                                                        <span className="tabular-nums">{Number(item.quantity)}</span>
                                                        {' × '}
                                                        {item.product_name}
                                                    </p>

                                                    {item.modifiers.length > 0 ? (
                                                        <ul className="space-y-0.5 text-sm">
                                                            {item.modifiers.map((modifier, index) => (
                                                                <li key={`${item.uuid}-${index}`} className="opacity-80">
                                                                    <i
                                                                        className="fa-solid fa-plus mr-1 text-[10px]"
                                                                        aria-hidden="true"
                                                                    />
                                                                    {modifier.name}
                                                                    {Number(modifier.quantity) > 1
                                                                        ? ` ×${Number(modifier.quantity)}`
                                                                        : ''}
                                                                </li>
                                                            ))}
                                                        </ul>
                                                    ) : null}

                                                    {item.notes ? (
                                                        <p className="rounded-box border-l-4 border-warning bg-warning/15 px-2 py-1 text-sm font-medium">
                                                            <i className="fa-solid fa-note-sticky mr-1" aria-hidden="true" />
                                                            {item.notes}
                                                        </p>
                                                    ) : null}

                                                    <div className="flex items-center justify-between">
                                                        <span className={cn('text-sm font-medium', waitingClass(item.minutes_waiting))}>
                                                            <i className="fa-regular fa-clock mr-1" aria-hidden="true" />
                                                            hace {item.minutes_waiting} min
                                                        </span>
                                                    </div>

                                                    {action ? (
                                                        <button
                                                            type="button"
                                                            className={cn('btn h-14 w-full text-lg', action.className)}
                                                            disabled={busy === item.uuid}
                                                            onClick={() => advance(item)}
                                                        >
                                                            {busy === item.uuid ? (
                                                                <span className="loading loading-spinner" />
                                                            ) : (
                                                                <i className={`fa-solid ${action.icon}`} aria-hidden="true" />
                                                            )}
                                                            {action.label}
                                                        </button>
                                                    ) : null}
                                                </div>
                                            </article>
                                        );
                                    })
                                )}
                            </div>
                        </section>
                    );
                })}
            </div>
        </AppLayout>
    );
}
