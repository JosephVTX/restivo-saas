import { Head } from '@inertiajs/react';
import AppLayout from '@/components/layout/AppLayout';
import { EmptyState } from '@/components/ui/EmptyState';
import { Field } from '@/components/ui/Field';
import { Modal } from '@/components/ui/Modal';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { RowActions } from '@/components/ui/RowActions';
import { SearchInput } from '@/components/ui/SearchInput';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { TableShell } from '@/components/ui/TableShell';
import { useCrud } from '@/hooks/use-crud';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { useResource } from '@/hooks/use-resource';
import { zoneSchema, type ZoneValues } from '@/schemas/zone';
import type { Zone } from '@/types';

const empty: ZoneValues = { name: '', sort_order: 0, is_active: true };

export default function ZonesIndex() {
    const { search, query, change, page, setPage } = useDebouncedSearch();
    const { items: zones, meta, isLoading, mutate } = useResource<Zone>('/api/v1/zones', {
        page,
        filter: { search: query },
    });

    const crud = useCrud<ZoneValues, Zone>({
        endpoint: '/api/v1/zones',
        schema: zoneSchema,
        empty,
        mutate,
        toValues: (zone) => ({ name: zone.name, sort_order: zone.sort_order, is_active: zone.is_active }),
        removeLabel: (zone) => `¿Eliminar la zona "${zone.name}"?`,
    });

    return (
        <AppLayout title="Zonas">
            <Head title="Zonas" />
            <PageHeader
                title="Zonas"
                description="Organiza el salón por ambientes."
                actions={
                    <button type="button" className="btn btn-primary btn-sm" onClick={crud.openCreate}>
                        <i className="fa-solid fa-plus" aria-hidden="true" /> Nueva zona
                    </button>
                }
            />

            <div className="mt-4 flex flex-wrap gap-2">
                <SearchInput value={search} onChange={change} placeholder="Buscar zona…" />
            </div>

            <div className="mt-4">
                <TableShell
                    head={['Zona', 'Estado', 'Orden', '']}
                    isLoading={isLoading}
                    isEmpty={zones.length === 0}
                    empty={
                        <EmptyState icon="fa-map" title="Aún no hay zonas">
                            Crea tu primera zona para organizar las mesas del salón.
                        </EmptyState>
                    }
                >
                    {zones.map((zone) => (
                        <tr key={zone.uuid} className="hover">
                            <td className="font-medium">{zone.name}</td>
                            <td>
                                <StatusBadge
                                    status={zone.is_active ? 'active' : 'archived'}
                                    label={zone.is_active ? 'Activa' : 'Inactiva'}
                                />
                            </td>
                            <td className="text-sm opacity-70">{zone.sort_order}</td>
                            <td>
                                <RowActions onEdit={() => crud.openEdit(zone)} onDelete={() => crud.remove(zone)} />
                            </td>
                        </tr>
                    ))}
                </TableShell>
            </div>

            <div className="mt-4">
                <Pagination meta={meta} onChange={setPage} />
            </div>

            <Modal
                open={crud.open}
                title={crud.editing ? 'Editar zona' : 'Nueva zona'}
                onClose={crud.close}
                footer={
                    <>
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
                    <Field label="Orden" hint="Menor número aparece primero.">
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
