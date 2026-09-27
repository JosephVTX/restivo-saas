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
import { menuCategorySchema, type MenuCategoryValues } from '@/schemas/menuCategory';
import type { EnumOption, MenuCategory, Station } from '@/types';

interface Props {
    stations: EnumOption<Station>[];
}

const empty: MenuCategoryValues = {
    name: '',
    description: '',
    station: 'kitchen',
    color: '',
    sort_order: 0,
    is_active: true,
};

export default function MenuCategories({ stations }: Props) {
    const { search, query, change, page, setPage } = useDebouncedSearch();
    const { items: categories, meta, isLoading, mutate } = useResource<MenuCategory>('/api/v1/menu-categories', {
        page,
        filter: { search: query },
    });

    const crud = useCrud<MenuCategoryValues, MenuCategory>({
        endpoint: '/api/v1/menu-categories',
        schema: menuCategorySchema,
        empty,
        mutate,
        toValues: (category) => ({
            name: category.name,
            description: category.description ?? '',
            station: category.station,
            color: category.color ?? '',
            sort_order: category.sort_order,
            is_active: category.is_active,
        }),
        removeLabel: (category) => `¿Eliminar la categoría "${category.name}"?`,
    });

    return (
        <AppLayout title="Categorías">
            <Head title="Categorías" />
            <PageHeader
                title="Categorías"
                description="Agrupa los productos de la carta."
                actions={
                    <button type="button" className="btn btn-primary btn-sm" onClick={crud.openCreate}>
                        <i className="fa-solid fa-plus" aria-hidden="true" /> Nueva categoría
                    </button>
                }
            />

            <div className="mt-4 flex flex-wrap gap-2">
                <SearchInput value={search} onChange={change} placeholder="Buscar categoría…" />
            </div>

            <div className="mt-4">
                <TableShell
                    head={['Categoría', 'Estación', 'Orden', 'Estado', '']}
                    isLoading={isLoading}
                    isEmpty={categories.length === 0}
                    empty={
                        <EmptyState icon="fa-tags" title="Aún no hay categorías">
                            Crea tu primera categoría para organizar la carta.
                        </EmptyState>
                    }
                >
                    {categories.map((category) => (
                        <tr key={category.uuid} className="hover">
                            <td>
                                <div className="font-medium">{category.name}</div>
                                {category.description ? (
                                    <div className="max-w-xs truncate text-xs opacity-60">{category.description}</div>
                                ) : null}
                            </td>
                            <td className="text-sm">{category.station_label}</td>
                            <td className="text-sm opacity-70">{category.sort_order}</td>
                            <td>
                                <StatusBadge
                                    status={category.is_active ? 'active' : 'archived'}
                                    label={category.is_active ? 'Activa' : 'Inactiva'}
                                />
                            </td>
                            <td>
                                <RowActions
                                    onEdit={() => crud.openEdit(category)}
                                    onDelete={() => crud.remove(category)}
                                />
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
                title={crud.editing ? 'Editar categoría' : 'Nueva categoría'}
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
                    <Field label="Descripción" error={crud.errors.description}>
                        <input
                            className="input w-full"
                            value={crud.values.description}
                            onChange={(event) => crud.setValues({ ...crud.values, description: event.target.value })}
                        />
                    </Field>
                    <div className="grid grid-cols-2 gap-3">
                        <Field label="Estación de preparación" error={crud.errors.station}>
                            <select
                                className="select w-full"
                                value={crud.values.station}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, station: event.target.value as Station })
                                }
                            >
                                {stations.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field label="Orden" error={crud.errors.sort_order}>
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
