import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/components/layout/AppLayout';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { PaymentModal } from '@/components/ui/PaymentModal';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { useCan } from '@/hooks/use-can';
import { useResource } from '@/hooks/use-resource';
import { api } from '@/lib/http';
import { cn } from '@/lib/utils';
import type { EnumOption, Order, PaymentMethod } from '@/types';

interface Props {
    paymentMethodOptions: EnumOption<PaymentMethod>[];
}

type Scope = 'mine' | 'all';

function formatPrice(value: string | number | null | undefined): string {
    return `S/ ${Number(value ?? 0).toFixed(2)}`;
}

function minutesSince(value: string | null): number {
    if (!value) {
        return 0;
    }

    return Math.max(0, Math.floor((Date.now() - new Date(value).getTime()) / 60000));
}

export default function WaiterOrders({ paymentMethodOptions }: Props) {
    const can = useCan();
    const [scope, setScope] = useState<Scope>('mine');
    const [payingOrder, setPayingOrder] = useState<Order | null>(null);

    const { items: orders, isLoading, mutate } = useResource<Order>('/api/v1/orders', {
        per_page: 100,
        sort: '-created_at',
        filter: { active: 1, mine: scope === 'mine' ? 1 : undefined },
    });

    const send = async (order: Order) => {
        await api.post(`/api/v1/orders/${order.uuid}/send`);
        await mutate();
    };

    return (
        <AppLayout title="Mis pedidos">
            <Head title="Mis pedidos" />
            <PageHeader
                title={scope === 'mine' ? 'Mis pedidos' : 'Pedidos activos'}
                description="Tus mesas activas, listas para enviar o cobrar."
                actions={
                    <div role="tablist" className="tabs tabs-box">
                        <button
                            type="button"
                            role="tab"
                            className={cn('tab', scope === 'mine' && 'tab-active')}
                            onClick={() => setScope('mine')}
                        >
                            <i className="fa-solid fa-user mr-1" aria-hidden="true" /> Mis mesas
                        </button>
                        <button
                            type="button"
                            role="tab"
                            className={cn('tab', scope === 'all' && 'tab-active')}
                            onClick={() => setScope('all')}
                        >
                            <i className="fa-solid fa-users mr-1" aria-hidden="true" /> Todas
                        </button>
                    </div>
                }
            />

            {isLoading ? (
                <div className="flex justify-center py-16">
                    <span className="loading loading-spinner" />
                </div>
            ) : orders.length === 0 ? (
                <div className="mt-6">
                    <EmptyState icon="fa-receipt" title={scope === 'mine' ? 'No tienes pedidos activos' : 'No hay pedidos activos'}>
                        Abre una mesa desde el POS para tomar un pedido.
                    </EmptyState>
                </div>
            ) : (
                <>
                    <p className="mt-4 text-sm opacity-60">
                        {orders.length} {orders.length === 1 ? 'pedido activo' : 'pedidos activos'}
                    </p>

                    <div className="mt-2 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        {orders.map((order) => (
                            <div key={order.uuid} className="card border border-base-300 bg-base-100">
                                <div className="card-body gap-3 p-4">
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <h3 className="truncate text-base font-semibold">
                                                <i className="fa-solid fa-chair mr-1 opacity-60" aria-hidden="true" />
                                                {order.dining_table?.name ?? order.type_label}
                                            </h3>
                                            <p className="mt-0.5 text-xs opacity-60">
                                                #{order.number} · {minutesSince(order.opened_at)} min
                                                {order.items_count ? ` · ${order.items_count} líneas` : ''}
                                            </p>
                                        </div>
                                        <StatusBadge status={order.status} label={order.status_label} />
                                    </div>

                                    <div className="flex items-end justify-between">
                                        <div>
                                            <p className="text-xs uppercase tracking-wide opacity-60">Total</p>
                                            <p className="text-2xl font-bold tabular-nums">{formatPrice(order.total)}</p>
                                        </div>
                                        {Number(order.remaining) > 0.001 ? (
                                            <div className="text-right">
                                                <p className="text-xs uppercase tracking-wide opacity-60">Por cobrar</p>
                                                <p className="font-semibold tabular-nums text-warning">
                                                    {formatPrice(order.remaining)}
                                                </p>
                                            </div>
                                        ) : null}
                                    </div>

                                    <div className="card-actions items-center justify-end gap-1">
                                        {order.status === 'open' && can('orders.update') ? (
                                            <button
                                                type="button"
                                                className="btn btn-ghost btn-sm"
                                                onClick={() => void send(order)}
                                            >
                                                <i className="fa-solid fa-paper-plane" aria-hidden="true" /> Enviar
                                            </button>
                                        ) : null}
                                        <Link href={`/app/pos?order=${order.uuid}`} className="btn btn-outline btn-sm">
                                            <i className="fa-solid fa-pen-to-square" aria-hidden="true" /> Abrir
                                        </Link>
                                        {can('payments.create') && Number(order.remaining) > 0.001 ? (
                                            <button
                                                type="button"
                                                className="btn btn-primary btn-sm"
                                                onClick={() => setPayingOrder(order)}
                                            >
                                                <i className="fa-solid fa-hand-holding-dollar" aria-hidden="true" /> Cobrar
                                            </button>
                                        ) : null}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </>
            )}

            <PaymentModal
                key={payingOrder?.uuid ?? 'closed'}
                order={payingOrder}
                paymentMethodOptions={paymentMethodOptions}
                onClose={() => setPayingOrder(null)}
                onPaid={() => {
                    void mutate();
                }}
            />
        </AppLayout>
    );
}
