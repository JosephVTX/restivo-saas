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
import {
    modifierGroupSchema,
    type ModifierGroupValues,
    type ModifierValues,
} from '@/schemas/modifierGroup';
import type { EnumOption, ModifierGroup, ModifierSelectionType } from '@/types';

interface Props {
    selection_types: EnumOption<ModifierSelectionType>[];
}

const empty: ModifierGroupValues = {
    name: '',
    selection_type: 'single',
    is_required: false,
    min_selections: 0,
    max_selections: 1,
    sort_order: 0,
    is_active: true,
    modifiers: [],
};

export default function ModifiersIndex({ selection_types }: Props) {
    const { search, query, change, page, setPage } = useDebouncedSearch();
    const { items: groups, meta, isLoading, mutate } = useResource<ModifierGroup>('/api/v1/modifier-groups', {
        page,
        filter: { search: query },
    });

    const crud = useCrud<ModifierGroupValues, ModifierGroup>({
        endpoint: '/api/v1/modifier-groups',
        schema: modifierGroupSchema,
        empty,
        mutate,
        toValues: (group) => ({
            name: group.name,
            selection_type: group.selection_type,
            is_required: group.is_required,
            min_selections: group.min_selections,
            max_selections: group.max_selections,
            sort_order: group.sort_order,
            is_active: group.is_active,
            modifiers: (group.modifiers ?? []).map((modifier) => ({
                uuid: modifier.uuid,
                name: modifier.name,
                price: Number(modifier.price),
                is_default: modifier.is_default,
                sort_order: modifier.sort_order,
                is_active: modifier.is_active,
            })),
        }),
        removeLabel: (group) => `¿Eliminar el grupo "${group.name}"?`,
    });

    const updateModifier = (index: number, patch: Partial<ModifierValues>) => {
        crud.setValues({
            ...crud.values,
            modifiers: crud.values.modifiers.map((modifier, i) => (i === index ? { ...modifier, ...patch } : modifier)),
        });
    };

    const addModifier = () => {
        crud.setValues({
            ...crud.values,
            modifiers: [
                ...crud.values.modifiers,
                { name: '', price: 0, is_default: false, sort_order: crud.values.modifiers.length, is_active: true },
            ],
        });
    };

    const removeModifier = (index: number) => {
        crud.setValues({ ...crud.values, modifiers: crud.values.modifiers.filter((_, i) => i !== index) });
    };

    return (
        <AppLayout title="Opciones">
            <Head title="Opciones" />
            <PageHeader
                title="Opciones"
                description="Grupos de opciones (término, extras, salsas…) que se asignan a los productos."
                actions={
                    <button type="button" className="btn btn-primary btn-sm" onClick={crud.openCreate}>
                        <i className="fa-solid fa-plus" aria-hidden="true" /> Nuevo grupo
                    </button>
                }
            />

            <div className="mt-4 flex flex-wrap gap-2">
                <SearchInput value={search} onChange={change} placeholder="Buscar grupo…" />
            </div>

            <div className="mt-4">
                <TableShell
                    head={['Grupo', 'Tipo', 'Opciones', 'Estado', '']}
                    isLoading={isLoading}
                    isEmpty={groups.length === 0}
                    empty={
                        <EmptyState icon="fa-list-check" title="Aún no hay grupos de opciones">
                            Crea un grupo para ofrecer variantes y extras.
                        </EmptyState>
                    }
                >
                    {groups.map((group) => (
                        <tr key={group.uuid} className="hover">
                            <td className="font-medium">{group.name}</td>
                            <td className="text-sm">{group.selection_type_label}</td>
                            <td className="text-sm opacity-70">{group.modifiers?.length ?? 0}</td>
                            <td>
                                <StatusBadge
                                    status={group.is_active ? 'active' : 'archived'}
                                    label={group.is_active ? 'Activo' : 'Inactivo'}
                                />
                            </td>
                            <td>
                                <RowActions onEdit={() => crud.openEdit(group)} onDelete={() => crud.remove(group)} />
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
                title={crud.editing ? 'Editar grupo de opciones' : 'Nuevo grupo de opciones'}
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
                    <div className="grid grid-cols-2 gap-3">
                        <Field label="Tipo de selección" error={crud.errors.selection_type}>
                            <select
                                className="select w-full"
                                value={crud.values.selection_type}
                                onChange={(event) =>
                                    crud.setValues({
                                        ...crud.values,
                                        selection_type: event.target.value as ModifierSelectionType,
                                    })
                                }
                            >
                                {selection_types.map((option) => (
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
                    <div className="grid grid-cols-2 gap-3">
                        <Field label="Mínimo a elegir" error={crud.errors.min_selections}>
                            <input
                                type="number"
                                min={0}
                                className="input w-full"
                                value={crud.values.min_selections}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, min_selections: Number(event.target.value) })
                                }
                            />
                        </Field>
                        <Field label="Máximo a elegir" error={crud.errors.max_selections}>
                            <input
                                type="number"
                                min={0}
                                className="input w-full"
                                value={crud.values.max_selections}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, max_selections: Number(event.target.value) })
                                }
                            />
                        </Field>
                    </div>
                    <div className="flex flex-wrap gap-6">
                        <Field label="Obligatorio">
                            <input
                                type="checkbox"
                                className="toggle"
                                checked={crud.values.is_required}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, is_required: event.target.checked })
                                }
                            />
                        </Field>
                        <Field label="Activo">
                            <input
                                type="checkbox"
                                className="toggle"
                                checked={crud.values.is_active}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, is_active: event.target.checked })
                                }
                            />
                        </Field>
                    </div>

                    <Field label="Opciones" error={crud.errors.modifiers}>
                        <div className="space-y-2">
                            {crud.values.modifiers.length === 0 ? (
                                <p className="text-xs opacity-60">Sin opciones. Agrega al menos una.</p>
                            ) : (
                                crud.values.modifiers.map((modifier, index) => (
                                    <div
                                        key={modifier.uuid ?? `nuevo-${index}`}
                                        className="flex flex-wrap items-center gap-2 rounded-box border border-base-300 p-2"
                                    >
                                        <input
                                            className="input input-sm min-w-40 flex-1"
                                            placeholder="Nombre"
                                            value={modifier.name}
                                            onChange={(event) => updateModifier(index, { name: event.target.value })}
                                        />
                                        <label className="input input-sm w-28">
                                            <span className="opacity-50">S/</span>
                                            <input
                                                type="number"
                                                min={0}
                                                step="0.01"
                                                value={modifier.price}
                                                onChange={(event) =>
                                                    updateModifier(index, { price: Number(event.target.value) })
                                                }
                                            />
                                        </label>
                                        <label className="flex cursor-pointer items-center gap-1 text-xs">
                                            <input
                                                type="checkbox"
                                                className="checkbox checkbox-sm"
                                                checked={modifier.is_default}
                                                onChange={(event) =>
                                                    updateModifier(index, { is_default: event.target.checked })
                                                }
                                            />
                                            Por defecto
                                        </label>
                                        <button
                                            type="button"
                                            className="btn btn-ghost btn-xs text-error"
                                            title="Quitar opción"
                                            onClick={() => removeModifier(index)}
                                        >
                                            <i className="fa-solid fa-trash" aria-hidden="true" />
                                        </button>
                                    </div>
                                ))
                            )}
                            <button type="button" className="btn btn-ghost btn-xs" onClick={addModifier}>
                                <i className="fa-solid fa-plus" aria-hidden="true" /> Agregar opción
                            </button>
                        </div>
                    </Field>
                </div>
            </Modal>
        </AppLayout>
    );
}
