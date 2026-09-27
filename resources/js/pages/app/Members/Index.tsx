import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/components/layout/AppLayout';
import { EmptyState } from '@/components/ui/EmptyState';
import { Field } from '@/components/ui/Field';
import { Modal } from '@/components/ui/Modal';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { RowActions } from '@/components/ui/RowActions';
import { TableShell } from '@/components/ui/TableShell';
import { useCrud } from '@/hooks/use-crud';
import { useResource } from '@/hooks/use-resource';
import { roleLabel } from '@/lib/labels';
import { formatDate } from '@/lib/utils';
import { memberSchema, type MemberValues } from '@/schemas/member';
import type { EnumOption, Membership, RoleName } from '@/types';

interface Props {
    roles: EnumOption<RoleName>[];
}

const empty: MemberValues = { email: '', role: 'member', job_title: '' };

export default function MembersIndex({ roles }: Props) {
    const [page, setPage] = useState(1);
    const { items: members, meta, isLoading, mutate } = useResource<Membership>('/api/v1/members', { page });

    const crud = useCrud<MemberValues, Membership>({
        endpoint: '/api/v1/members',
        schema: memberSchema,
        empty,
        mutate,
        removeLabel: (member) => `¿Quitar a ${member.user.name}?`,
    });

    return (
        <AppLayout title="Miembros">
            <Head title="Miembros" />
            <PageHeader
                title="Miembros"
                description="Personas con acceso a este espacio de trabajo."
                actions={
                    <button type="button" className="btn btn-primary btn-sm" onClick={crud.openCreate}>
                        <i className="fa-solid fa-user-plus" aria-hidden="true" /> Invitar
                    </button>
                }
            />

            <div className="mt-4">
                <TableShell
                    head={['Nombre', 'Correo', 'Rol', 'Se unió', '']}
                    isLoading={isLoading}
                    isEmpty={members.length === 0}
                    empty={<EmptyState icon="fa-users" title="Aún no hay miembros" />}
                >
                    {members.map((member) => (
                        <tr key={member.uuid} className="hover">
                            <td className="font-medium">{member.user.name}</td>
                            <td className="opacity-80">{member.user.email}</td>
                            <td>
                                <span className="badge badge-ghost badge-sm">
                                    {roleLabel(member.roles?.[0])}
                                </span>
                            </td>
                            <td className="text-sm opacity-70">{formatDate(member.joined_at)}</td>
                            <td>
                                <RowActions onDelete={() => crud.remove(member)} />
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
                title="Invitar miembro"
                description="Si el correo no existe, se creará una cuenta nueva."
                onClose={crud.close}
                footer={
                    <>
                        <button type="button" className="btn btn-ghost" onClick={crud.close}>
                            Cancelar
                        </button>
                        <button type="button" className="btn btn-primary" onClick={crud.save} disabled={crud.saving}>
                            {crud.saving ? <span className="loading loading-spinner loading-sm" /> : 'Enviar invitación'}
                        </button>
                    </>
                }
            >
                <div className="space-y-1">
                    <Field label="Correo electrónico" error={crud.errors.email}>
                        <input
                            type="email"
                            className="input w-full"
                            value={crud.values.email}
                            onChange={(event) => crud.setValues({ ...crud.values, email: event.target.value })}
                        />
                    </Field>
                    <div className="grid grid-cols-2 gap-3">
                        <Field label="Rol">
                            <select
                                className="select w-full"
                                value={crud.values.role}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, role: event.target.value as MemberValues['role'] })
                                }
                            >
                                {roles.map((role) => (
                                    <option key={role.value} value={role.value}>
                                        {role.label}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field label="Cargo">
                            <input
                                className="input w-full"
                                value={crud.values.job_title}
                                onChange={(event) => crud.setValues({ ...crud.values, job_title: event.target.value })}
                            />
                        </Field>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
