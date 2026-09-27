import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import useSWR from 'swr';
import AppLayout from '@/components/layout/AppLayout';
import { EmptyState } from '@/components/ui/EmptyState';
import { Modal, confirmDelete } from '@/components/ui/Modal';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { useResource } from '@/hooks/use-resource';
import { api, fetcher } from '@/lib/http';
import { cn } from '@/lib/utils';
import type {
    DiningTable,
    MenuData,
    MenuModifierGroup,
    MenuProduct,
    Order,
    OrderItem,
    TableStatus,
    Zone,
} from '@/types';

type Mode = 'dine_in' | 'takeaway';

const tableCard: Record<TableStatus, string> = {
    available: 'border-success/50 bg-success/10',
    occupied: 'border-warning/50 bg-warning/15',
    billing: 'border-info/50 bg-info/10',
    reserved: 'border-neutral/40 bg-neutral/10',
    cleaning: 'border-base-300 bg-base-200',
};

function formatPrice(value: string | number): string {
    return `S/ ${Number(value ?? 0).toFixed(2)}`;
}

function minutesSince(value: string | null): number {
    if (!value) {
        return 0;
    }

    return Math.max(0, Math.floor((Date.now() - new Date(value).getTime()) / 60000));
}

export default function PosIndex() {
    const { url } = usePage();
    const initialOrderUuid = useMemo(() => {
        const query = url.split('?')[1] ?? '';

        return new URLSearchParams(query).get('order');
    }, [url]);

    const [mode, setMode] = useState<Mode>('dine_in');
    const [selectedUuid, setSelectedUuid] = useState<string | null>(initialOrderUuid);
    const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
    const [activeProduct, setActiveProduct] = useState<MenuProduct | null>(null);
    const [selections, setSelections] = useState<Record<string, string[]>>({});
    const [saving, setSaving] = useState(false);

    const tables = useResource<DiningTable>('/api/v1/dining-tables', { per_page: 100, sort: 'sort_order' });
    const zones = useResource<Zone>('/api/v1/zones', { per_page: 100, sort: 'sort_order' });
    const activeOrders = useResource<Order>('/api/v1/orders', { per_page: 100, filter: { active: 1 } });

    const { data: menuResponse } = useSWR<{ data: MenuData }>('/api/v1/menu', fetcher);
    const menu = menuResponse?.data;

    const { data: orderResponse, mutate: mutateOrder } = useSWR<{ data: Order }>(
        selectedUuid ? `/api/v1/orders/${selectedUuid}` : null,
        fetcher,
    );
    const order = orderResponse?.data ?? null;

    const orderByTable = useMemo(() => {
        const map = new Map<string, Order>();

        for (const current of activeOrders.items) {
            if (current.dining_table?.uuid) {
                map.set(current.dining_table.uuid, current);
            }
        }

        return map;
    }, [activeOrders.items]);

    const tableGroups = useMemo(() => {
        const map = new Map<string, { key: string; name: string; tables: DiningTable[] }>();

        for (const table of tables.items) {
            const key = table.zone?.uuid ?? 'none';
            const group = map.get(key) ?? { key, name: table.zone?.name ?? 'Sin zona', tables: [] };
            group.tables.push(table);
            map.set(key, group);
        }

        const zoneOrder = new Map(zones.items.map((zone, index) => [zone.uuid, index]));

        return [...map.values()].toSorted(
            (a, b) => (zoneOrder.get(a.key) ?? Number.MAX_SAFE_INTEGER) - (zoneOrder.get(b.key) ?? Number.MAX_SAFE_INTEGER),
        );
    }, [tables.items, zones.items]);

    const products = useMemo(() => {
        if (!menu) {
            return [];
        }

        return selectedCategory
            ? menu.products.filter((product) => product.menu_category_uuid === selectedCategory)
            : menu.products;
    }, [menu, selectedCategory]);

    const refreshOrder = async () => {
        await Promise.all([mutateOrder(), activeOrders.mutate()]);
    };

    const openTable = async (table: DiningTable) => {
        const existing = orderByTable.get(table.uuid);

        if (existing) {
            setSelectedUuid(existing.uuid);

            return;
        }

        setSaving(true);

        try {
            const response = await api.post<{ data: Order }>('/api/v1/orders', {
                type: 'dine_in',
                dining_table: table.uuid,
            });

            await Promise.all([activeOrders.mutate(), tables.mutate()]);
            setSelectedUuid(response.data.uuid);
        } finally {
            setSaving(false);
        }
    };

    const createTakeaway = async () => {
        setSaving(true);

        try {
            const response = await api.post<{ data: Order }>('/api/v1/orders', { type: 'takeaway' });

            await activeOrders.mutate();
            setSelectedUuid(response.data.uuid);
        } finally {
            setSaving(false);
        }
    };

    const addItem = async (product: MenuProduct, modifierUuids: string[], quantity = 1) => {
        if (!order) {
            return;
        }

        if (modifierUuids.length === 0) {
            const existing = (order.items ?? []).find(
                (item) => item.product_uuid === product.uuid && item.modifiers.length === 0,
            );

            if (existing) {
                await api.patch(`/api/v1/order-items/${existing.uuid}`, {
                    quantity: Number(existing.quantity) + quantity,
                });
                await refreshOrder();

                return;
            }
        }

        await api.post(`/api/v1/orders/${order.uuid}/items`, {
            product: product.uuid,
            quantity,
            modifiers: modifierUuids,
        });
        await refreshOrder();
    };

    const openProduct = (product: MenuProduct) => {
        if (!product.is_available) {
            return;
        }

        if (product.modifier_groups.length === 0) {
            void addItem(product, []);

            return;
        }

        const initial: Record<string, string[]> = {};

        for (const group of product.modifier_groups) {
            initial[group.uuid] = group.modifiers.filter((modifier) => modifier.is_default).map((modifier) => modifier.uuid);
        }

        setSelections(initial);
        setActiveProduct(product);
    };

    const toggleModifier = (group: MenuModifierGroup, modifierUuid: string) => {
        setSelections((current) => {
            const selected = current[group.uuid] ?? [];

            if (group.selection_type === 'single') {
                return { ...current, [group.uuid]: selected.includes(modifierUuid) ? [] : [modifierUuid] };
            }

            return {
                ...current,
                [group.uuid]: selected.includes(modifierUuid)
                    ? selected.filter((uuid) => uuid !== modifierUuid)
                    : [...selected, modifierUuid],
            };
        });
    };

    const missingRequired =
        activeProduct?.modifier_groups.some(
            (group) => group.is_required && (selections[group.uuid]?.length ?? 0) < Math.max(1, group.min_selections),
        ) ?? false;

    const confirmModifiers = async () => {
        if (!activeProduct) {
            return;
        }

        const product = activeProduct;
        const modifierUuids = Object.values(selections).flat();
        setActiveProduct(null);
        await addItem(product, modifierUuids);
    };

    const changeQuantity = async (item: OrderItem, delta: number) => {
        const next = Number(item.quantity) + delta;

        if (next < 0.5) {
            return;
        }

        await api.patch(`/api/v1/order-items/${item.uuid}`, { quantity: next });
        await refreshOrder();
    };

    const voidItem = async (item: OrderItem) => {
        if (!confirmDelete(`¿Quitar "${item.product_name}" del pedido?`)) {
            return;
        }

        await api.delete(`/api/v1/order-items/${item.uuid}`);
        await refreshOrder();
    };

    const sendToKitchen = async () => {
        if (!order) {
            return;
        }

        await api.post(`/api/v1/orders/${order.uuid}/send`);
        await refreshOrder();
    };

    const backToTables = () => {
        setSelectedUuid(null);
        router.visit('/app/pos');
    };

    return (
        <AppLayout title="POS">
            <Head title="POS" />
            <PageHeader
                title="Punto de venta"
                description="Toma rápida de pedidos del mozo."
                actions={
                    <div className="join">
                        <button
                            type="button"
                            className={cn('btn btn-sm join-item', mode === 'dine_in' && 'btn-primary')}
                            onClick={() => setMode('dine_in')}
                        >
                            <i className="fa-solid fa-chair" aria-hidden="true" /> En mesa
                        </button>
                        <button
                            type="button"
                            className={cn('btn btn-sm join-item', mode === 'takeaway' && 'btn-primary')}
                            onClick={() => setMode('takeaway')}
                        >
                            <i className="fa-solid fa-bag-shopping" aria-hidden="true" /> Para llevar
                        </button>
                    </div>
                }
            />

            {!order ? (
                <div className="mt-6">
                    {mode === 'dine_in' ? (
                        tables.isLoading ? (
                            <div className="flex justify-center py-16">
                                <span className="loading loading-spinner" />
                            </div>
                        ) : tableGroups.length === 0 ? (
                            <div className="rounded-box border border-base-300 bg-base-100">
                                <EmptyState icon="fa-chair" title="No hay mesas registradas">
                                    Crea mesas para tomar pedidos en el salón.
                                </EmptyState>
                            </div>
                        ) : (
                            <div className="space-y-6">
                                {tableGroups.map((group) => (
                                    <section key={group.key}>
                                        <h2 className="mb-2 text-sm font-semibold uppercase tracking-wide opacity-60">
                                            {group.name}
                                        </h2>
                                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                            {group.tables.map((table) => {
                                                const currentOrder = orderByTable.get(table.uuid);

                                                return (
                                                    <button
                                                        key={table.uuid}
                                                        type="button"
                                                        disabled={saving}
                                                        onClick={() => openTable(table)}
                                                        className={cn(
                                                            'card min-h-32 items-center justify-center gap-1 border-2 p-3 text-center transition hover:shadow-md active:scale-[0.97]',
                                                            tableCard[table.status],
                                                        )}
                                                    >
                                                        <span className="text-lg font-bold leading-none">{table.name}</span>
                                                        <span className="text-xs opacity-70">
                                                            <i className="fa-solid fa-chair" aria-hidden="true" />{' '}
                                                            {table.capacity} pers.
                                                        </span>
                                                        {currentOrder ? (
                                                            <>
                                                                <span className="text-sm font-semibold tabular-nums">
                                                                    {formatPrice(currentOrder.total)}
                                                                </span>
                                                                <span className="text-xs opacity-70">
                                                                    <i className="fa-regular fa-clock" aria-hidden="true" />{' '}
                                                                    {minutesSince(currentOrder.opened_at)} min
                                                                </span>
                                                            </>
                                                        ) : (
                                                            <StatusBadge
                                                                status={table.status}
                                                                label={table.status_label}
                                                            />
                                                        )}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </section>
                                ))}
                            </div>
                        )
                    ) : (
                        <div className="grid place-items-center py-16">
                            <button
                                type="button"
                                className="btn btn-primary btn-lg h-40 w-full max-w-md flex-col gap-3"
                                onClick={createTakeaway}
                                disabled={saving}
                            >
                                {saving ? (
                                    <span className="loading loading-spinner" />
                                ) : (
                                    <i className="fa-solid fa-bag-shopping text-4xl" aria-hidden="true" />
                                )}
                                Nuevo pedido para llevar
                            </button>
                        </div>
                    )}
                </div>
            ) : (
                <div className="mt-6 grid gap-4 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <div className="flex gap-2 overflow-x-auto pb-2">
                            <button
                                type="button"
                                className={cn('btn btn-sm', selectedCategory === null && 'btn-primary')}
                                onClick={() => setSelectedCategory(null)}
                            >
                                Todo
                            </button>
                            {(menu?.categories ?? []).map((category) => (
                                <button
                                    key={category.uuid}
                                    type="button"
                                    className={cn(
                                        'btn btn-sm whitespace-nowrap',
                                        selectedCategory === category.uuid && 'btn-primary',
                                    )}
                                    onClick={() => setSelectedCategory(category.uuid)}
                                >
                                    {category.name}
                                </button>
                            ))}
                        </div>

                        <div className="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            {products.map((product) => (
                                <button
                                    key={product.uuid}
                                    type="button"
                                    disabled={!product.is_available}
                                    onClick={() => openProduct(product)}
                                    className={cn(
                                        'card min-h-24 items-start justify-between gap-2 border border-base-300 bg-base-100 p-3 text-left transition hover:shadow-md active:scale-[0.97]',
                                        !product.is_available && 'opacity-50',
                                    )}
                                >
                                    <span className="font-medium leading-tight">{product.name}</span>
                                    <span className="text-sm font-semibold tabular-nums">
                                        {formatPrice(product.price)}
                                    </span>
                                </button>
                            ))}
                        </div>
                    </div>

                    <aside className="rounded-box border border-base-300 bg-base-100 p-4 lg:sticky lg:top-20 lg:self-start">
                        <div className="flex items-start justify-between gap-2">
                            <div>
                                <p className="text-lg font-semibold">#{order.number}</p>
                                <p className="text-xs opacity-70">
                                    {order.type_label}
                                    {order.dining_table ? ` · ${order.dining_table.name}` : ''}
                                </p>
                            </div>
                            <StatusBadge status={order.status} label={order.status_label} />
                        </div>

                        <div className="mt-4 max-h-[45vh] space-y-2 overflow-y-auto">
                            {(order.items ?? []).length === 0 ? (
                                <p className="py-8 text-center text-sm opacity-60">
                                    Aún no hay líneas. Toca un producto para agregarlo.
                                </p>
                            ) : (
                                (order.items ?? []).map((item) => (
                                    <div key={item.uuid} className="rounded-box border border-base-300 p-2">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">{item.product_name}</p>
                                                {item.modifiers.length > 0 ? (
                                                    <p className="truncate text-xs opacity-60">
                                                        {item.modifiers.map((modifier) => modifier.name).join(', ')}
                                                    </p>
                                                ) : null}
                                                <p className="text-xs opacity-60">
                                                    {formatPrice(item.unit_price)}
                                                    {Number(item.modifiers_total) > 0
                                                        ? ` + ${formatPrice(item.modifiers_total)}`
                                                        : ''}
                                                </p>
                                            </div>
                                            <span className="text-sm font-semibold tabular-nums">
                                                {formatPrice(item.line_total)}
                                            </span>
                                        </div>
                                        <div className="mt-2 flex items-center justify-between">
                                            <div className="join">
                                                <button
                                                    type="button"
                                                    className="btn btn-xs join-item"
                                                    onClick={() => changeQuantity(item, -1)}
                                                >
                                                    <i className="fa-solid fa-minus" aria-hidden="true" />
                                                </button>
                                                <span className="btn btn-xs join-item pointer-events-none tabular-nums">
                                                    {Number(item.quantity)}
                                                </span>
                                                <button
                                                    type="button"
                                                    className="btn btn-xs join-item"
                                                    onClick={() => changeQuantity(item, 1)}
                                                >
                                                    <i className="fa-solid fa-plus" aria-hidden="true" />
                                                </button>
                                            </div>
                                            <button
                                                type="button"
                                                className="btn btn-ghost btn-xs text-error"
                                                title="Quitar"
                                                onClick={() => voidItem(item)}
                                            >
                                                <i className="fa-solid fa-trash" aria-hidden="true" />
                                            </button>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>

                        <div className="mt-4 space-y-1 border-t border-base-300 pt-3 text-sm">
                            <div className="flex justify-between">
                                <span className="opacity-70">Subtotal</span>
                                <span className="tabular-nums">{formatPrice(order.subtotal)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="opacity-70">IGV</span>
                                <span className="tabular-nums">{formatPrice(order.tax_total)}</span>
                            </div>
                            <div className="flex justify-between text-base font-semibold">
                                <span>Total</span>
                                <span className="tabular-nums">{formatPrice(order.total)}</span>
                            </div>
                        </div>

                        <div className="mt-4 grid gap-2">
                            <button
                                type="button"
                                className="btn btn-primary"
                                onClick={sendToKitchen}
                                disabled={(order.items ?? []).length === 0}
                            >
                                <i className="fa-solid fa-paper-plane" aria-hidden="true" /> Enviar a cocina
                            </button>
                            <button type="button" className="btn btn-ghost" onClick={backToTables}>
                                <i className="fa-solid fa-arrow-left" aria-hidden="true" /> Volver a mesas
                            </button>
                        </div>
                    </aside>
                </div>
            )}

            <Modal
                open={activeProduct !== null}
                title={activeProduct?.name ?? ''}
                description="Selecciona las opciones del producto."
                onClose={() => setActiveProduct(null)}
                footer={
                    <>
                        <button type="button" className="btn btn-ghost" onClick={() => setActiveProduct(null)}>
                            Cancelar
                        </button>
                        <button
                            type="button"
                            className="btn btn-primary"
                            onClick={confirmModifiers}
                            disabled={missingRequired}
                        >
                            Agregar
                        </button>
                    </>
                }
            >
                <div className="space-y-4">
                    {(activeProduct?.modifier_groups ?? []).map((group) => (
                        <div key={group.uuid}>
                            <p className="text-sm font-semibold">
                                {group.name}
                                {group.is_required ? <span className="text-error"> *</span> : null}
                            </p>
                            <div className="mt-2 grid grid-cols-2 gap-2">
                                {group.modifiers.map((modifier) => {
                                    const selected = (selections[group.uuid] ?? []).includes(modifier.uuid);

                                    return (
                                        <button
                                            key={modifier.uuid}
                                            type="button"
                                            className={cn('btn justify-between', selected && 'btn-primary')}
                                            onClick={() => toggleModifier(group, modifier.uuid)}
                                        >
                                            <span>{modifier.name}</span>
                                            {Number(modifier.price) > 0 ? (
                                                <span className="tabular-nums">
                                                    +{formatPrice(modifier.price)}
                                                </span>
                                            ) : null}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </div>
            </Modal>
        </AppLayout>
    );
}
