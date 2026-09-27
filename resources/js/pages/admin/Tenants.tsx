import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '@/components/layout/AdminLayout';
import { Field } from '@/components/ui/Field';
import { Modal, confirmDelete } from '@/components/ui/Modal';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { RowActions } from '@/components/ui/RowActions';
import { SearchInput } from '@/components/ui/SearchInput';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { StatusFilter } from '@/components/ui/StatusFilter';
import { TableShell } from '@/components/ui/TableShell';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { useResource } from '@/hooks/use-resource';
import { api, validationErrors } from '@/lib/http';
import { formatDate } from '@/lib/utils';
import { tenantAccessSchema, tenantSchema, type TenantAccessValues, type TenantValues } from '@/schemas/tenant';
import type { EnumOption, PlanDuration, PlanDurationOption, Tenant, TenantStatus } from '@/types';

interface Props {
    statuses: EnumOption<TenantStatus>[];
    durations: PlanDurationOption[];
}

const emptyTenant: TenantValues = { name: '', status: 'active', locale: 'es', duration: '' };
const emptyAccess: TenantAccessValues = { name: '', email: '', password: '' };

function previewEndDate(duration: PlanDuration): string {
    const date = new Date();

    if (duration.endsWith('_month') || duration.endsWith('_months')) {
        date.setMonth(date.getMonth() + Number.parseInt(duration, 10));
    } else {
        date.setDate(date.getDate() + Number.parseInt(duration, 10));
    }

    return formatDate(date.toISOString());
}

export default function Tenants({ statuses, durations }: Props) {
    const { search, query, change, page, setPage } = useDebouncedSearch();
    const [status, setStatus] = useState('');
    const { items: tenants, meta, isLoading, mutate } = useResource<Tenant>('/api/v1/admin/tenants', {
        page,
        filter: { search: query, status },
    });

    const [createOpen, setCreateOpen] = useState(false);
    const [grantTarget, setGrantTarget] = useState<Tenant | null>(null);
    const [editing, setEditing] = useState<Tenant | null>(null);
    const [values, setValues] = useState<TenantValues>(emptyTenant);
    const [access, setAccess] = useState<TenantAccessValues>(emptyAccess);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);
    const [temporaryPassword, setTemporaryPassword] = useState<string | null>(null);

    const reset = () => {
        setErrors({});
        setTemporaryPassword(null);
    };

    const openCreate = () => {
        setEditing(null);
        setValues(emptyTenant);
        setAccess(emptyAccess);
        reset();
        setCreateOpen(true);
    };

    const openEdit = (tenant: Tenant) => {
        setEditing(tenant);
        setValues({
            name: tenant.name,
            status: tenant.status,
            locale: tenant.locale === 'en' ? 'en' : 'es',
            duration: '',
        });
        reset();
        setCreateOpen(true);
    };

    const openGrant = (tenant: Tenant) => {
        setGrantTarget(tenant);
        setAccess(emptyAccess);
        reset();
    };

    const validateAccess = (prefix = ''): boolean => {
        const parsed = tenantAccessSchema.safeParse(access);

        if (!parsed.success) {
            setErrors(
                Object.fromEntries(
                    parsed.error.issues.map((issue) => [`${prefix}${String(issue.path[0])}`, issue.message]),
                ),
            );

            return false;
        }

        return true;
    };

    const save = async () => {
        const parsed = tenantSchema.safeParse(values);

        if (!parsed.success) {
            setErrors(Object.fromEntries(parsed.error.issues.map((issue) => [String(issue.path[0]), issue.message])));

            return;
        }

        if (!editing && !validateAccess('owner_')) {
            return;
        }

        setSaving(true);
        setErrors({});

        const { duration, ...rest } = parsed.data;
        const payload = duration ? { ...rest, duration } : rest;

        try {
            if (editing) {
                await api.patch(`/api/v1/admin/tenants/${editing.uuid}`, payload);
                setCreateOpen(false);
            } else {
                const response = await api.post<{ temporary_password: string | null }>('/api/v1/admin/tenants', {
                    ...payload,
                    owner_name: access.name,
                    owner_email: access.email,
                    owner_password: access.password || undefined,
                });
                setTemporaryPassword(response.temporary_password);
            }

            await mutate();
        } catch (error) {
            setErrors(validationErrors(error));
        } finally {
            setSaving(false);
        }
    };

    const grant = async () => {
        if (!grantTarget || !validateAccess()) {
            return;
        }

        setSaving(true);
        setErrors({});

        try {
            const response = await api.post<{ temporary_password: string | null }>(
                `/api/v1/admin/tenants/${grantTarget.uuid}/members`,
                {
                    name: access.name,
                    email: access.email,
                    role: 'member',
                    password: access.password || undefined,
                },
            );
            setTemporaryPassword(response.temporary_password);
            await mutate();
        } catch (error) {
            setErrors(validationErrors(error));
        } finally {
            setSaving(false);
        }
    };

    const remove = async (tenant: Tenant) => {
        if (!confirmDelete(`¿Eliminar "${tenant.name}"? Esta acción no se puede deshacer.`)) {
            return;
        }

        await api.delete(`/api/v1/admin/tenants/${tenant.uuid}`);
        await mutate();
    };

    return (
        <AdminLayout title="Clientes">
            <Head title="Clientes" />
            <PageHeader
                title="Clientes"
                description="Crea espacios de trabajo y otorga acceso a tus clientes."
                actions={
                    <button type="button" className="btn btn-primary btn-sm" onClick={openCreate}>
                        <i className="fa-solid fa-plus" aria-hidden="true" /> Nuevo cliente
                    </button>
                }
            />

            <div className="mt-4 flex flex-wrap gap-2">
                <SearchInput value={search} onChange={change} placeholder="Buscar por nombre…" />
                <StatusFilter
                    value={status}
                    options={statuses}
                    onChange={(value) => {
                        setStatus(value);
                        setPage(1);
                    }}
                />
            </div>

            <div className="mt-4">
                <TableShell
                    head={['Cliente', 'Estado', 'Miembros', 'Creado', '']}
                    isLoading={isLoading}
                    isEmpty={tenants.length === 0}
                >
                    {tenants.map((tenant) => (
                        <tr key={tenant.uuid} className="hover">
                            <td>
                                <div className="font-medium">{tenant.name}</div>
                                <div className="text-xs opacity-60">{tenant.slug}</div>
                            </td>
                            <td>
                                <StatusBadge status={tenant.status} label={tenant.status_label} />
                                {tenant.expires_at ? (
                                    <div
                                        className={`text-xs ${
                                            tenant.has_expired
                                                ? 'text-error'
                                                : tenant.is_expiring_soon
                                                  ? 'text-warning'
                                                  : 'opacity-60'
                                        }`}
                                    >
                                        {tenant.has_expired
                                            ? 'Vencido'
                                            : tenant.is_expiring_soon
                                              ? `Vence en ${tenant.days_until_expiry} día(s)`
                                              : `Vence ${formatDate(tenant.expires_at)}`}
                                    </div>
                                ) : null}
                            </td>
                            <td className="tabular-nums">{tenant.members_count ?? 0}</td>
                            <td className="text-sm opacity-70">{formatDate(tenant.created_at)}</td>
                            <td>
                                <RowActions
                                    extra={
                                        <button
                                            type="button"
                                            className="btn btn-ghost btn-xs"
                                            title="Otorgar acceso"
                                            onClick={() => openGrant(tenant)}
                                        >
                                            <i className="fa-solid fa-user-plus" aria-hidden="true" />
                                        </button>
                                    }
                                    onEdit={() => openEdit(tenant)}
                                    onDelete={() => remove(tenant)}
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
                open={createOpen}
                title={editing ? 'Editar cliente' : 'Nuevo cliente y propietario'}
                description={editing ? undefined : 'Se creará el espacio de trabajo y la cuenta del propietario.'}
                onClose={() => setCreateOpen(false)}
                footer={
                    <>
                        <button type="button" className="btn btn-ghost" onClick={() => setCreateOpen(false)}>
                            {temporaryPassword ? 'Cerrar' : 'Cancelar'}
                        </button>
                        {temporaryPassword === null ? (
                            <button type="button" className="btn btn-primary" onClick={save} disabled={saving}>
                                {saving ? <span className="loading loading-spinner loading-sm" /> : 'Guardar'}
                            </button>
                        ) : null}
                    </>
                }
            >
                {temporaryPassword ? (
                    <div className="alert alert-success">
                        <i className="fa-solid fa-circle-check" aria-hidden="true" />
                        <div>
                            <p className="font-medium">Acceso otorgado.</p>
                            <p className="mt-1 text-sm">
                                Contraseña temporal:{' '}
                                <code className="rounded bg-base-100 px-2 py-1 font-mono">{temporaryPassword}</code>
                            </p>
                            <p className="mt-1 text-xs opacity-70">
                                Compártela con el cliente; podrá cambiarla luego.
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="space-y-1">
                        <Field label="Nombre del espacio" error={errors.name}>
                            <input
                                className="input w-full"
                                value={values.name}
                                onChange={(event) => setValues({ ...values, name: event.target.value })}
                            />
                        </Field>

                        <Field label="Estado">
                            <select
                                className="select w-full"
                                value={values.status}
                                onChange={(event) =>
                                    setValues({ ...values, status: event.target.value as TenantValues['status'] })
                                }
                            >
                                {statuses.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </Field>

                        <Field
                            label="Periodo de servicio"
                            error={errors.duration}
                            hint={
                                values.duration
                                    ? `Vencerá el ${previewEndDate(values.duration as PlanDuration)}.`
                                    : editing
                                      ? `Actual: ${editing.expires_at ? formatDate(editing.expires_at) : 'sin vencimiento'}. Elige un periodo para renovarlo.`
                                      : 'Elige un periodo de prueba o un plan de pago.'
                            }
                        >
                            <select
                                className="select w-full"
                                value={values.duration ?? ''}
                                onChange={(event) => {
                                    const duration = event.target.value as PlanDuration | '';
                                    const option = durations.find((item) => item.value === duration);

                                    setValues({
                                        ...values,
                                        duration,
                                        status: option ? (option.is_trial ? 'trial' : 'active') : values.status,
                                    });
                                }}
                            >
                                <option value="">
                                    {editing ? 'No cambiar' : 'Sin periodo (no vence)'}
                                </option>
                                <optgroup label="Prueba">
                                    {durations
                                        .filter((option) => option.is_trial)
                                        .map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                </optgroup>
                                <optgroup label="Plan de pago">
                                    {durations
                                        .filter((option) => !option.is_trial)
                                        .map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                </optgroup>
                            </select>
                        </Field>

                        {!editing ? (
                            <>
                                <div className="divider my-2 text-xs uppercase tracking-wide opacity-60">
                                    Propietario (acceso del cliente)
                                </div>
                                <Field label="Nombre del propietario" error={errors.owner_name}>
                                    <input
                                        className="input w-full"
                                        value={access.name}
                                        onChange={(event) => setAccess({ ...access, name: event.target.value })}
                                    />
                                </Field>
                                <Field label="Correo del propietario" error={errors.owner_email}>
                                    <input
                                        type="email"
                                        className="input w-full"
                                        value={access.email}
                                        onChange={(event) => setAccess({ ...access, email: event.target.value })}
                                    />
                                </Field>
                                <Field
                                    label="Contraseña del propietario"
                                    error={errors.owner_password}
                                    hint="Déjala vacía para generarla automáticamente."
                                >
                                    <input
                                        type="text"
                                        className="input w-full"
                                        value={access.password}
                                        onChange={(event) => setAccess({ ...access, password: event.target.value })}
                                    />
                                </Field>
                            </>
                        ) : null}
                    </div>
                )}
            </Modal>

            <Modal
                open={grantTarget !== null}
                title={`Otorgar acceso a ${grantTarget?.name ?? ''}`}
                onClose={() => setGrantTarget(null)}
                footer={
                    <>
                        <button type="button" className="btn btn-ghost" onClick={() => setGrantTarget(null)}>
                            {temporaryPassword ? 'Cerrar' : 'Cancelar'}
                        </button>
                        {temporaryPassword === null ? (
                            <button type="button" className="btn btn-primary" onClick={grant} disabled={saving}>
                                {saving ? <span className="loading loading-spinner loading-sm" /> : 'Otorgar acceso'}
                            </button>
                        ) : null}
                    </>
                }
            >
                {temporaryPassword ? (
                    <div className="alert alert-success">
                        <i className="fa-solid fa-circle-check" aria-hidden="true" />
                        <div>
                            <p className="font-medium">Acceso otorgado.</p>
                            <p className="mt-1 text-sm">
                                Contraseña temporal:{' '}
                                <code className="rounded bg-base-100 px-2 py-1 font-mono">{temporaryPassword}</code>
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="space-y-1">
                        <Field label="Nombre">
                            <input
                                className="input w-full"
                                value={access.name}
                                onChange={(event) => setAccess({ ...access, name: event.target.value })}
                            />
                        </Field>
                        <Field label="Correo electrónico" error={errors.email}>
                            <input
                                type="email"
                                className="input w-full"
                                value={access.email}
                                onChange={(event) => setAccess({ ...access, email: event.target.value })}
                            />
                        </Field>
                        <Field
                            label="Contraseña (opcional)"
                            error={errors.password}
                            hint="Déjala vacía para generarla automáticamente."
                        >
                            <input
                                type="text"
                                className="input w-full"
                                value={access.password}
                                onChange={(event) => setAccess({ ...access, password: event.target.value })}
                            />
                        </Field>
                    </div>
                )}
            </Modal>
        </AdminLayout>
    );
}
