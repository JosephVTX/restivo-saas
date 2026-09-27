import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/components/layout/AppLayout';
import { confirmDelete } from '@/components/ui/Modal';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { RowActions } from '@/components/ui/RowActions';
import { SearchInput } from '@/components/ui/SearchInput';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { StatusFilter } from '@/components/ui/StatusFilter';
import { TableShell } from '@/components/ui/TableShell';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { useCan } from '@/hooks/use-can';
import { useResource } from '@/hooks/use-resource';
import { api } from '@/lib/http';
import { formatDate } from '@/lib/utils';
import type { EnumOption, Order, OrderStatus, OrderType } from '@/types';

interface Props {
    orderStatusOptions: EnumOption<OrderStatus>[];
    orderTypeOptions: EnumOption<OrderType>[];
}

function formatPrice(value: string): string {
    return `S/ ${Number(value ?? 0).toFixed(2)}`;
}

export default function OrdersIndex({ orderStatusOptions, orderTypeOptions }: Props) {
    const can = useCan();
    const { search, query, change, page, setPage } = useDebouncedSearch();
    const [status, setStatus] = useState('');
    const [type, setType] = useState('');

    const { items: orders, meta, isLoading, mutate } = useResource<Order>('/api/v1/orders', {
        page,
        sort: '-created_at',
        filter: { search: query, status, type },
    });

    const send = async (order: Order) => {
        await api.post(`/api/v1/orders/${order.uuid}/send`);
        await mutate();
    };

    const cancel = async (order: Order) => {
        if (!confirmDelete(`¿Anular el pedido #${order.number}?`)) {
            return;
        }

        await api.post(`/api/v1/orders/${order.uuid}/cancel`);
        await mutate();
    };

    return (
        <AppLayout title="Pedidos">
            <Head title="Pedidos" />
            <PageHeader title="Pedidos" description="Pedidos del salón, para llevar y delivery." />

            <div className="mt-4 flex flex-wrap gap-2">
                <SearchInput value={search} onChange={change} placeholder="Buscar por número…" />
                <StatusFilter
                    value={status}
                    options={orderStatusOptions}
                    onChange={(value) => {
                        setStatus(value);
                        setPage(1);
                    }}
                />
                <StatusFilter
                    value={type}
                    options={orderTypeOptions}
                    allLabel="Todos los tipos"
                    onChange={(value) => {
                        setType(value);
                        setPage(1);
                    }}
                />
            </div>

            <div className="mt-4">
                <TableShell
                    head={['Número', 'Tipo', 'Mesa', 'Mozo', 'Estado', 'Total', 'Abierto', '']}
                    isLoading={isLoading}
                    isEmpty={orders.length === 0}
                    empty={
                        <EmptyState icon="fa-receipt" title="Aún no hay pedidos">
                            Abre un pedido desde el POS para empezar.
                        </EmptyState>
                    }
                >
                    {orders.map((order) => (
                        <tr key={order.uuid} className="hover">
                            <td className="font-medium tabular-nums">#{order.number}</td>
                            <td className="text-sm">{order.type_label}</td>
                            <td className="text-sm">{order.dining_table?.name ?? '—'}</td>
                            <td className="text-sm">{order.waiter?.name ?? '—'}</td>
                            <td>
                                <StatusBadge status={order.status} label={order.status_label} />
                            </td>
                            <td className="font-medium tabular-nums">{formatPrice(order.total)}</td>
                            <td className="text-sm opacity-70">{formatDate(order.opened_at)}</td>
                            <td>
                                <RowActions
                                    extra={
                                        <>
                                            <Link
                                                href={`/app/pos?order=${order.uuid}`}
                                                className="btn btn-ghost btn-xs"
                                                title="Abrir en POS"
                                            >
                                                <i className="fa-solid fa-cash-register" aria-hidden="true" />
                                            </Link>
                                            {can('payments.create') && Number(order.remaining) > 0.001 ? (
                                                <Link
                                                    href="/app/cash"
                                                    className="btn btn-ghost btn-xs"
                                                    title="Cobrar"
                                                >
                                                    <i className="fa-solid fa-hand-holding-dollar" aria-hidden="true" />
                                                </Link>
                                            ) : null}
                                            {order.status === 'paid' && can('documents.create') ? (
                                                <Link
                                                    href={`/app/documents?order=${order.uuid}`}
                                                    className="btn btn-ghost btn-xs"
                                                    title="Comprobante"
                                                >
                                                    <i className="fa-solid fa-file-invoice" aria-hidden="true" />
                                                </Link>
                                            ) : null}
                                            {order.status === 'open' ? (
                                                <button
                                                    type="button"
                                                    className="btn btn-ghost btn-xs"
                                                    title="Enviar a cocina"
                                                    onClick={() => send(order)}
                                                >
                                                    <i className="fa-solid fa-paper-plane" aria-hidden="true" />
                                                </button>
                                            ) : null}
                                            <button
                                                type="button"
                                                className="btn btn-ghost btn-xs text-error"
                                                title="Anular"
                                                onClick={() => cancel(order)}
                                            >
                                                <i className="fa-solid fa-ban" aria-hidden="true" />
                                            </button>
                                        </>
                                    }
                                />
                            </td>
                        </tr>
                    ))}
                </TableShell>
            </div>

            <div className="mt-4">
                <Pagination meta={meta} onChange={setPage} />
            </div>
        </AppLayout>
    );
}
