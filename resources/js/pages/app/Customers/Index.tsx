import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/components/layout/AppLayout';
import { EmptyState } from '@/components/ui/EmptyState';
import { Field } from '@/components/ui/Field';
import { Modal } from '@/components/ui/Modal';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { RowActions } from '@/components/ui/RowActions';
import { SearchInput } from '@/components/ui/SearchInput';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { StatusFilter } from '@/components/ui/StatusFilter';
import { TableShell } from '@/components/ui/TableShell';
import { useCrud } from '@/hooks/use-crud';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { useResource } from '@/hooks/use-resource';
import { customerSchema, type CustomerValues } from '@/schemas/customer';
import type { Customer, EnumOption, IdentityDocumentType } from '@/types';

interface Props {
    identityDocumentTypeOptions: EnumOption<IdentityDocumentType>[];
}

const empty: CustomerValues = {
    doc_type: 'dni',
    doc_number: '',
    name: '',
    email: '',
    phone: '',
    address: '',
    notes: '',
    is_active: true,
};

const statusOptions = [
    { value: '1', label: 'Activos' },
    { value: '0', label: 'Inactivos' },
];

export default function CustomersIndex({ identityDocumentTypeOptions }: Props) {
    const { search, query, change, page, setPage } = useDebouncedSearch();
    const [status, setStatus] = useState('');
    const { items: customers, meta, isLoading, mutate } = useResource<Customer>('/api/v1/customers', {
        page,
        filter: { search: query, is_active: status },
    });

    const crud = useCrud<CustomerValues, Customer>({
        endpoint: '/api/v1/customers',
        schema: customerSchema,
        empty,
        mutate,
        toValues: (customer) => ({
            doc_type: customer.doc_type,
            doc_number: customer.doc_number ?? '',
            name: customer.name,
            email: customer.email ?? '',
            phone: customer.phone ?? '',
            address: customer.address ?? '',
            notes: customer.notes ?? '',
            is_active: customer.is_active,
        }),
        removeLabel: (customer) => `¿Eliminar "${customer.name}"?`,
    });

    return (
        <AppLayout title="Clientes">
            <Head title="Clientes" />
            <PageHeader
                title="Clientes"
                description="Directorio de clientes para comprobantes y pedidos."
                actions={
                    <button type="button" className="btn btn-primary btn-sm" onClick={crud.openCreate}>
                        <i className="fa-solid fa-plus" aria-hidden="true" /> Nuevo cliente
                    </button>
                }
            />

            <div className="mt-4 flex flex-wrap gap-2">
                <SearchInput value={search} onChange={change} placeholder="Buscar por nombre, documento o correo…" />
                <StatusFilter
                    value={status}
                    options={statusOptions}
                    allLabel="Todos"
                    onChange={(value) => {
                        setStatus(value);
                        setPage(1);
                    }}
                />
            </div>

            <div className="mt-4">
                <TableShell
                    head={['Cliente', 'Documento', 'Correo', 'Teléfono', 'Estado', '']}
                    isLoading={isLoading}
                    isEmpty={customers.length === 0}
                    empty={
                        <EmptyState icon="fa-users" title="Aún no hay clientes">
                            Registra tu primer cliente para empezar.
                        </EmptyState>
                    }
                >
                    {customers.map((customer) => (
                        <tr key={customer.uuid} className="hover">
                            <td className="font-medium">{customer.name}</td>
                            <td className="text-sm">
                                {customer.doc_number ? (
                                    <>
                                        <span className="opacity-60">{customer.doc_type_label}</span>{' '}
                                        <span className="tabular-nums">{customer.doc_number}</span>
                                    </>
                                ) : (
                                    <span className="opacity-40">—</span>
                                )}
                            </td>
                            <td className="text-sm opacity-70">{customer.email ?? '—'}</td>
                            <td className="text-sm opacity-70">{customer.phone ?? '—'}</td>
                            <td>
                                <StatusBadge
                                    status={customer.is_active ? 'active' : 'archived'}
                                    label={customer.is_active ? 'Activo' : 'Inactivo'}
                                />
                            </td>
                            <td>
                                <RowActions
                                    onEdit={() => crud.openEdit(customer)}
                                    onDelete={() => crud.remove(customer)}
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
                title={crud.editing ? 'Editar cliente' : 'Nuevo cliente'}
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
                    <Field label="Nombre o razón social" error={crud.errors.name}>
                        <input
                            className="input w-full"
                            value={crud.values.name}
                            onChange={(event) => crud.setValues({ ...crud.values, name: event.target.value })}
                        />
                    </Field>

                    <div className="grid grid-cols-2 gap-3">
                        <Field label="Tipo de documento" error={crud.errors.doc_type}>
                            <select
                                className="select w-full"
                                value={crud.values.doc_type}
                                onChange={(event) =>
                                    crud.setValues({
                                        ...crud.values,
                                        doc_type: event.target.value as CustomerValues['doc_type'],
                                    })
                                }
                            >
                                {identityDocumentTypeOptions.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field label="Número de documento" error={crud.errors.doc_number}>
                            <input
                                className="input w-full"
                                value={crud.values.doc_number}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, doc_number: event.target.value })
                                }
                            />
                        </Field>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <Field label="Correo electrónico" error={crud.errors.email}>
                            <input
                                type="email"
                                className="input w-full"
                                value={crud.values.email}
                                onChange={(event) => crud.setValues({ ...crud.values, email: event.target.value })}
                            />
                        </Field>
                        <Field label="Teléfono" error={crud.errors.phone}>
                            <input
                                className="input w-full"
                                value={crud.values.phone}
                                onChange={(event) => crud.setValues({ ...crud.values, phone: event.target.value })}
                            />
                        </Field>
                    </div>

                    <Field label="Dirección" error={crud.errors.address}>
                        <input
                            className="input w-full"
                            value={crud.values.address}
                            onChange={(event) => crud.setValues({ ...crud.values, address: event.target.value })}
                        />
                    </Field>

                    <Field label="Notas" error={crud.errors.notes}>
                        <textarea
                            className="textarea w-full"
                            rows={2}
                            value={crud.values.notes}
                            onChange={(event) => crud.setValues({ ...crud.values, notes: event.target.value })}
                        />
                    </Field>

                    <Field label="Estado">
                        <label className="label cursor-pointer justify-start gap-3">
                            <input
                                type="checkbox"
                                className="toggle"
                                checked={crud.values.is_active}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, is_active: event.target.checked })
                                }
                            />
                            <span>{crud.values.is_active ? 'Activo' : 'Inactivo'}</span>
                        </label>
                    </Field>
                </div>
            </Modal>
        </AppLayout>
    );
}
