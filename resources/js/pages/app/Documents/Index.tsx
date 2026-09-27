import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/components/layout/AppLayout';
import { confirmDelete } from '@/components/ui/Modal';
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
import { useCan } from '@/hooks/use-can';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { useResource } from '@/hooks/use-resource';
import { api, validationErrors } from '@/lib/http';
import { toast } from '@/lib/toast';
import { formatDate } from '@/lib/utils';
import { issueDocumentSchema, type IssueDocumentValues } from '@/schemas/document';
import type { Customer, Document, DocumentStatus, DocumentType, EnumOption, IdentityDocumentType, Order } from '@/types';

interface Props {
    documentTypeOptions: EnumOption<DocumentType>[];
    documentStatusOptions: EnumOption<DocumentStatus>[];
    identityDocumentTypeOptions: EnumOption<IdentityDocumentType>[];
}

function formatPrice(value: string | number | null | undefined): string {
    return `S/ ${Number(value ?? 0).toFixed(2)}`;
}

function errorMessage(error: unknown): string | null {
    return (error as { response?: { data?: { message?: string } } })?.response?.data?.message ?? null;
}

function errorsFor(error: unknown): Record<string, string> {
    const fieldErrors = validationErrors(error);
    const message = errorMessage(error);

    return message && Object.keys(fieldErrors).length === 0 ? { message } : fieldErrors;
}

const emptyIssue: IssueDocumentValues = {
    type: 'nota_venta',
    series: '',
    customer_id: '',
    customer_doc_type: '',
    customer_doc_number: '',
    customer_name: '',
    customer_address: '',
    notes: '',
};

function canAnnul(document: Document): boolean {
    return !['accepted', 'annulled'].includes(document.status);
}

export default function DocumentsIndex({ documentTypeOptions, documentStatusOptions, identityDocumentTypeOptions }: Props) {
    const can = useCan();
    const presetOrder = new URLSearchParams(window.location.search).get('order') ?? '';
    const { search, query, change, page, setPage } = useDebouncedSearch();
    const [type, setType] = useState('');
    const [status, setStatus] = useState('');

    const { items: documents, meta, isLoading, mutate } = useResource<Document>('/api/v1/documents', {
        page,
        sort: '-created_at',
        filter: { search: query, type, status },
    });

    const paidOrders = useResource<Order>('/api/v1/orders', {
        per_page: 50,
        sort: '-created_at',
        filter: { status: 'paid' },
    });

    const customerSearch = useDebouncedSearch();
    const customerResults = useResource<Customer>('/api/v1/customers', {
        per_page: 6,
        filter: { search: customerSearch.query, is_active: 1 },
    });

    const [open, setOpen] = useState(() => presetOrder !== '' && can('documents.create'));
    const [orderUuid, setOrderUuid] = useState(presetOrder);
    const [values, setValues] = useState<IssueDocumentValues>(emptyIssue);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);

    const selectCustomer = (customer: Customer) => {
        setValues((current) => ({
            ...current,
            customer_id: customer.uuid,
            customer_doc_type: customer.doc_type,
            customer_doc_number: customer.doc_number ?? '',
            customer_name: customer.name,
            customer_address: customer.address ?? '',
        }));
        customerSearch.change('');
    };

    const clearCustomer = () => {
        setValues((current) => ({ ...current, customer_id: '' }));
    };

    const openIssue = (newOrderUuid = '') => {
        setValues({ ...emptyIssue, series: '' });
        setErrors({});
        setOrderUuid(newOrderUuid);
        setOpen(true);
    };

    const showCustomer = values.type === 'boleta' || values.type === 'factura';
    const docTypeOptions =
        values.type === 'factura'
            ? identityDocumentTypeOptions.filter((option) => option.value === 'ruc')
            : identityDocumentTypeOptions;

    const submitIssue = async () => {
        const parsed = issueDocumentSchema.safeParse(values);

        if (!parsed.success) {
            setErrors(Object.fromEntries(parsed.error.issues.map((problem) => [String(problem.path[0]), problem.message])));

            return;
        }

        if (!orderUuid) {
            setErrors({ order: 'Selecciona un pedido pagado.' });

            return;
        }

        setSaving(true);
        setErrors({});

        try {
            await api.post(`/api/v1/orders/${orderUuid}/documents`, parsed.data);
            setOpen(false);
            await mutate();
            toast.success('Comprobante emitido.');
        } catch (error) {
            setErrors(errorsFor(error));
        } finally {
            setSaving(false);
        }
    };

    const annul = async (document: Document) => {
        if (!confirmDelete(`¿Anular el comprobante ${document.full_number}?`)) {
            return;
        }

        await api.post(`/api/v1/documents/${document.uuid}/annul`, {});
        await mutate();
        toast.success(`Comprobante ${document.full_number} anulado.`);
    };

    return (
        <AppLayout title="Comprobantes">
            <Head title="Comprobantes" />
            <PageHeader
                title="Comprobantes"
                description="Notas de venta, boletas y facturas emitidas."
                actions={
                    <button
                        type="button"
                        className="btn btn-primary btn-sm"
                        onClick={() => openIssue()}
                        disabled={!can('documents.create')}
                    >
                        <i className="fa-solid fa-file-circle-plus" aria-hidden="true" /> Emitir comprobante
                    </button>
                }
            />

            <div className="mt-4 flex flex-wrap gap-2">
                <SearchInput value={search} onChange={change} placeholder="Buscar por cliente o serie…" />
                <StatusFilter
                    value={type}
                    options={documentTypeOptions}
                    allLabel="Todos los tipos"
                    onChange={(value) => {
                        setType(value);
                        setPage(1);
                    }}
                />
                <StatusFilter
                    value={status}
                    options={documentStatusOptions}
                    onChange={(value) => {
                        setStatus(value);
                        setPage(1);
                    }}
                />
            </div>

            <div className="mt-4">
                <TableShell
                    head={['Tipo', 'Número', 'Cliente', 'Fecha', 'Total', 'Estado', '']}
                    isLoading={isLoading}
                    isEmpty={documents.length === 0}
                    empty={
                        <EmptyState icon="fa-file-invoice" title="Aún no hay comprobantes">
                            Emite un comprobante para un pedido pagado.
                        </EmptyState>
                    }
                >
                    {documents.map((document) => (
                        <tr key={document.uuid} className="hover">
                            <td className="text-sm">{document.type_label}</td>
                            <td className="font-medium tabular-nums">{document.full_number}</td>
                            <td>
                                <div className="text-sm">{document.customer_name ?? '—'}</div>
                                {document.customer_doc_number ? (
                                    <div className="text-xs opacity-60">
                                        {document.customer_doc_type_label} {document.customer_doc_number}
                                    </div>
                                ) : null}
                            </td>
                            <td className="text-sm opacity-70">{formatDate(document.issue_date)}</td>
                            <td className="font-medium tabular-nums">{formatPrice(document.total)}</td>
                            <td>
                                <StatusBadge status={document.status} label={document.status_label} />
                            </td>
                            <td>
                                <RowActions
                                    extra={
                                        <>
                                            {document.has_pdf ? (
                                                <button
                                                    type="button"
                                                    className="btn btn-ghost btn-xs"
                                                    title="Ver PDF"
                                                    onClick={() =>
                                                        window.open(`/api/v1/documents/${document.uuid}/pdf`, '_blank')
                                                    }
                                                >
                                                    <i className="fa-solid fa-file-pdf" aria-hidden="true" />
                                                </button>
                                            ) : null}
                                            {can('documents.create') && canAnnul(document) ? (
                                                <button
                                                    type="button"
                                                    className="btn btn-ghost btn-xs text-error"
                                                    title="Anular"
                                                    onClick={() => annul(document)}
                                                >
                                                    <i className="fa-solid fa-ban" aria-hidden="true" />
                                                </button>
                                            ) : null}
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

            <Modal
                open={open}
                title="Emitir comprobante"
                description="Genera una nota de venta, boleta o factura para un pedido pagado."
                onClose={() => setOpen(false)}
                footer={
                    <>
                        <button type="button" className="btn btn-ghost" onClick={() => setOpen(false)}>
                            Cancelar
                        </button>
                        <button type="button" className="btn btn-primary" disabled={saving} onClick={submitIssue}>
                            {saving ? <span className="loading loading-spinner" /> : null}
                            Emitir
                        </button>
                    </>
                }
            >
                <div className="space-y-1">
                    {errors.message ? (
                        <div className="alert alert-error">
                            <i className="fa-solid fa-circle-exclamation" aria-hidden="true" />
                            <span>{errors.message}</span>
                        </div>
                    ) : null}

                    <Field label="Pedido pagado" error={errors.order}>
                        <select
                            className="select w-full"
                            value={orderUuid}
                            onChange={(event) => setOrderUuid(event.target.value)}
                        >
                            <option value="">Selecciona un pedido…</option>
                            {paidOrders.items.map((order) => (
                                <option key={order.uuid} value={order.uuid}>
                                    #{order.number} · {formatPrice(order.total)}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field label="Cliente (opcional)" error={errors.customer_id}>
                        <div className="space-y-2">
                            <SearchInput
                                value={customerSearch.search}
                                onChange={(value) => {
                                    customerSearch.change(value);
                                    clearCustomer();
                                }}
                                placeholder="Buscar cliente por nombre o documento…"
                            />
                            {values.customer_id !== '' ? (
                                <div className="flex items-center justify-between rounded-box border border-base-300 px-3 py-2 text-sm">
                                    <span className="font-medium">{values.customer_name || 'Cliente seleccionado'}</span>
                                    <button type="button" className="btn btn-ghost btn-xs" onClick={clearCustomer}>
                                        <i className="fa-solid fa-xmark" aria-hidden="true" /> Quitar
                                    </button>
                                </div>
                            ) : customerSearch.query !== '' ? (
                                <div className="max-h-40 space-y-1 overflow-y-auto">
                                    {customerResults.items.length === 0 ? (
                                        <p className="text-sm opacity-60">Sin resultados.</p>
                                    ) : (
                                        customerResults.items.map((customer) => (
                                            <button
                                                key={customer.uuid}
                                                type="button"
                                                className="btn btn-ghost btn-sm w-full justify-start"
                                                onClick={() => selectCustomer(customer)}
                                            >
                                                <span className="font-medium">{customer.name}</span>
                                                {customer.doc_number ? (
                                                    <span className="ml-2 text-xs opacity-60">
                                                        {customer.doc_number}
                                                    </span>
                                                ) : null}
                                            </button>
                                        ))
                                    )}
                                </div>
                            ) : null}
                        </div>
                    </Field>

                    <Field label="Tipo de comprobante" error={errors.type}>
                        <select
                            className="select w-full"
                            value={values.type}
                            onChange={(event) =>
                                setValues({ ...values, type: event.target.value as IssueDocumentValues['type'] })
                            }
                        >
                            {documentTypeOptions
                                .filter((option) => option.value !== 'nota_credito')
                                .map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                        </select>
                    </Field>

                    {values.type === 'nota_venta' ? (
                        <Field label="Nota (opcional)" error={errors.notes}>
                            <input
                                className="input w-full"
                                value={values.notes}
                                onChange={(event) => setValues({ ...values, notes: event.target.value })}
                            />
                        </Field>
                    ) : null}

                    {showCustomer ? (
                        <>
                            <div className="grid grid-cols-2 gap-3">
                                <Field label="Tipo de documento" error={errors.customer_doc_type}>
                                    <select
                                        className="select w-full"
                                        value={values.customer_doc_type}
                                        onChange={(event) =>
                                            setValues({
                                                ...values,
                                                customer_doc_type: event.target
                                                    .value as IssueDocumentValues['customer_doc_type'],
                                            })
                                        }
                                    >
                                        <option value="">Selecciona…</option>
                                        {docTypeOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </Field>
                                <Field label="Número de documento" error={errors.customer_doc_number}>
                                    <input
                                        className="input w-full"
                                        value={values.customer_doc_number}
                                        onChange={(event) =>
                                            setValues({ ...values, customer_doc_number: event.target.value })
                                        }
                                    />
                                </Field>
                            </div>

                            <Field label="Nombre / Razón social" error={errors.customer_name}>
                                <input
                                    className="input w-full"
                                    value={values.customer_name}
                                    onChange={(event) => setValues({ ...values, customer_name: event.target.value })}
                                />
                            </Field>

                            <Field label="Dirección (opcional)" error={errors.customer_address}>
                                <input
                                    className="input w-full"
                                    value={values.customer_address}
                                    onChange={(event) => setValues({ ...values, customer_address: event.target.value })}
                                />
                            </Field>
                        </>
                    ) : null}
                </div>
            </Modal>
        </AppLayout>
    );
}
