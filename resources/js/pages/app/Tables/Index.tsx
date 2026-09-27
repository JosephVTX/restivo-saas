import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import AppLayout from '@/components/layout/AppLayout';
import { EmptyState } from '@/components/ui/EmptyState';
import { Field } from '@/components/ui/Field';
import { Modal } from '@/components/ui/Modal';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { SearchInput } from '@/components/ui/SearchInput';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { StatusFilter } from '@/components/ui/StatusFilter';
import { useCrud } from '@/hooks/use-crud';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { useResource } from '@/hooks/use-resource';
import { cn } from '@/lib/utils';
import { diningTableSchema, type DiningTableValues } from '@/schemas/diningTable';
import type { DiningTable, EnumOption, TableStatus } from '@/types';

interface ZoneOption {
    uuid: string;
    name: string;
}

interface Props {
    statuses: EnumOption<TableStatus>[];
    zones: ZoneOption[];
}

const statusCard: Record<TableStatus, string> = {
    available: 'border-success/50 bg-success/10',
    occupied: 'border-warning/50 bg-warning/15',
    billing: 'border-info/50 bg-info/10',
    reserved: 'border-neutral/40 bg-neutral/10',
    cleaning: 'border-base-300 bg-base-200',
};

const empty: DiningTableValues = {
    zone_id: null,
    name: '',
    capacity: 4,
    status: 'available',
    sort_order: 0,
    is_active: true,
};

export default function TablesIndex({ statuses, zones }: Props) {
    const { search, query, change, page, setPage } = useDebouncedSearch();
    const [zone, setZone] = useState('');
    const [status, setStatus] = useState('');

    const { items: tables, meta, isLoading, mutate } = useResource<DiningTable>('/api/v1/dining-tables', {
        page,
        per_page: 100,
        filter: { search: query, zone, status },
    });

    const crud = useCrud<DiningTableValues, DiningTable>({
        endpoint: '/api/v1/dining-tables',
        schema: diningTableSchema,
        empty,
        mutate,
        toValues: (table) => ({
            zone_id: table.zone?.uuid ?? null,
            name: table.name,
            capacity: table.capacity,
            status: table.status,
            sort_order: table.sort_order,
            is_active: table.is_active,
        }),
        removeLabel: (table) => `¿Eliminar "${table.name}"?`,
    });

    const groups = useMemo(() => {
        const map = new Map<string, { key: string; name: string; tables: DiningTable[] }>();

        for (const table of tables) {
            const key = table.zone?.uuid ?? 'none';
            const current = map.get(key) ?? { key, name: table.zone?.name ?? 'Sin zona', tables: [] };
            current.tables.push(table);
            map.set(key, current);
        }

        return [...map.values()];
    }, [tables]);

    return (
        <AppLayout title="Mesas">
            <Head title="Mesas" />
            <PageHeader
                title="Mesas"
                description="Mapa del salón. Toca una mesa para editarla."
                actions={
                    <button type="button" className="btn btn-primary btn-sm" onClick={crud.openCreate}>
                        <i className="fa-solid fa-plus" aria-hidden="true" /> Nueva mesa
                    </button>
                }
            />

            <div className="mt-4 flex flex-wrap gap-2">
                <select
                    className="select select-sm"
                    value={zone}
                    onChange={(event) => {
                        setZone(event.target.value);
                        setPage(1);
                    }}
                >
                    <option value="">Todas las zonas</option>
                    {zones.map((option) => (
                        <option key={option.uuid} value={option.uuid}>
                            {option.name}
                        </option>
                    ))}
                </select>
                <StatusFilter
                    value={status}
                    options={statuses}
                    onChange={(value) => {
                        setStatus(value);
                        setPage(1);
                    }}
                />
                <SearchInput value={search} onChange={change} placeholder="Buscar mesa…" />
            </div>

            <div className="mt-6">
                {isLoading ? (
                    <div className="flex justify-center py-16">
                        <span className="loading loading-spinner" />
                    </div>
                ) : tables.length === 0 ? (
                    <div className="rounded-box border border-base-300 bg-base-100">
                        <EmptyState icon="fa-chair" title="Aún no hay mesas">
                            Crea tu primera mesa para empezar a tomar pedidos.
                        </EmptyState>
                    </div>
                ) : (
                    <div className="space-y-6">
                        {groups.map((group) => (
                            <section key={group.key}>
                                <h2 className="mb-2 text-sm font-semibold uppercase tracking-wide opacity-60">
                                    {group.name}
                                </h2>
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                    {group.tables.map((table) => (
                                        <button
                                            key={table.uuid}
                                            type="button"
                                            onClick={() => crud.openEdit(table)}
                                            className={cn(
                                                'card min-h-28 items-center justify-center gap-2 border-2 p-3 text-center transition hover:shadow-md active:scale-[0.97]',
                                                statusCard[table.status],
                                            )}
                                        >
                                            <span className="text-lg font-bold leading-none">{table.name}</span>
                                            <span className="text-xs opacity-70">
                                                <i className="fa-solid fa-chair" aria-hidden="true" /> {table.capacity}{' '}
                                                pers.
                                            </span>
                                            <StatusBadge status={table.status} label={table.status_label} />
                                        </button>
                                    ))}
                                </div>
                            </section>
                        ))}
                    </div>
                )}
            </div>

            <div className="mt-4">
                <Pagination meta={meta} onChange={setPage} />
            </div>

            <Modal
                open={crud.open}
                title={crud.editing ? 'Editar mesa' : 'Nueva mesa'}
                onClose={crud.close}
                footer={
                    <>
                        {crud.editing ? (
                            <button
                                type="button"
                                className="btn btn-ghost mr-auto text-error"
                                onClick={async () => {
                                    if (crud.editing) {
                                        await crud.remove(crud.editing);
                                        crud.close();
                                    }
                                }}
                            >
                                <i className="fa-solid fa-trash" aria-hidden="true" /> Eliminar
                            </button>
                        ) : null}
                        <button type="button" className="btn btn-ghost" onClick={crud.close}>
                            Cancelar
                        </button>
                        <button type="button" className="btn btn-primary" onClick={crud.save} disabled={crud.saving}>
                            {crud.saving ? <span className="loading loading-spinner loading-sm" /> : 'Guardar'}
                        </button>
                    </>
                }
            >
                <div className="space-y-1">
                    <Field label="Nombre" error={crud.errors.name}>
                        <input
                            className="input w-full"
                            value={crud.values.name}
                            onChange={(event) => crud.setValues({ ...crud.values, name: event.target.value })}
                        />
                    </Field>
                    <div className="grid grid-cols-2 gap-3">
                        <Field label="Zona" error={crud.errors.zone_id}>
                            <select
                                className="select w-full"
                                value={crud.values.zone_id ?? ''}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, zone_id: event.target.value || null })
                                }
                            >
                                <option value="">Sin zona</option>
                                {zones.map((option) => (
                                    <option key={option.uuid} value={option.uuid}>
                                        {option.name}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field label="Capacidad" error={crud.errors.capacity}>
                            <input
                                type="number"
                                min={1}
                                className="input w-full"
                                value={crud.values.capacity}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, capacity: Number(event.target.value) })
                                }
                            />
                        </Field>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <Field label="Estado">
                            <select
                                className="select w-full"
                                value={crud.values.status}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, status: event.target.value as TableStatus })
                                }
                            >
                                {statuses.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field label="Orden">
                            <input
                                type="number"
                                min={0}
                                className="input w-full"
                                value={crud.values.sort_order}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, sort_order: Number(event.target.value) })
                                }
                            />
                        </Field>
                    </div>
                    <Field label="Activa">
                        <input
                            type="checkbox"
                            className="toggle"
                            checked={crud.values.is_active}
                            onChange={(event) => crud.setValues({ ...crud.values, is_active: event.target.checked })}
                        />
                    </Field>
                </div>
            </Modal>
        </AppLayout>
    );
}
