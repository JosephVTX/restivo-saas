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
import { formatDate } from '@/lib/utils';
import { projectSchema, type ProjectValues } from '@/schemas/project';
import type { EnumOption, Project, ProjectStatus } from '@/types';

interface Props {
    statuses: EnumOption<ProjectStatus>[];
}

const empty: ProjectValues = { name: '', description: '', status: 'draft', due_date: '' };

export default function ProjectsIndex({ statuses }: Props) {
    const { search, query, change, page, setPage } = useDebouncedSearch();
    const [status, setStatus] = useState('');
    const { items: projects, meta, isLoading, mutate } = useResource<Project>('/api/v1/projects', {
        page,
        filter: { search: query, status },
    });

    const crud = useCrud<ProjectValues, Project>({
        endpoint: '/api/v1/projects',
        schema: projectSchema,
        empty,
        mutate,
        toValues: (project) => ({
            name: project.name,
            description: project.description ?? '',
            status: project.status,
            due_date: project.due_date ?? '',
        }),
        removeLabel: (project) => `¿Eliminar "${project.name}"?`,
    });

    return (
        <AppLayout title="Proyectos">
            <Head title="Proyectos" />
            <PageHeader
                title="Proyectos"
                description="Recurso de ejemplo con aislamiento por tenant."
                actions={
                    <button type="button" className="btn btn-primary btn-sm" onClick={crud.openCreate}>
                        <i className="fa-solid fa-plus" aria-hidden="true" /> Nuevo proyecto
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
                    head={['Proyecto', 'Estado', 'Vencimiento', 'Creado', '']}
                    isLoading={isLoading}
                    isEmpty={projects.length === 0}
                    empty={
                        <EmptyState icon="fa-diagram-project" title="Aún no hay proyectos">
                            Crea tu primer proyecto para empezar.
                        </EmptyState>
                    }
                >
                    {projects.map((project) => (
                        <tr key={project.uuid} className="hover">
                            <td>
                                <div className="font-medium">{project.name}</div>
                                <div className="max-w-xs truncate text-xs opacity-60">{project.description}</div>
                            </td>
                            <td>
                                <StatusBadge status={project.status} label={project.status_label} />
                            </td>
                            <td className="text-sm opacity-70">{formatDate(project.due_date)}</td>
                            <td className="text-sm opacity-70">{formatDate(project.created_at)}</td>
                            <td>
                                <RowActions
                                    onEdit={() => crud.openEdit(project)}
                                    onDelete={() => crud.remove(project)}
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
                title={crud.editing ? 'Editar proyecto' : 'Nuevo proyecto'}
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
                        <textarea
                            className="textarea w-full"
                            rows={3}
                            value={crud.values.description}
                            onChange={(event) => crud.setValues({ ...crud.values, description: event.target.value })}
                        />
                    </Field>
                    <div className="grid grid-cols-2 gap-3">
                        <Field label="Estado">
                            <select
                                className="select w-full"
                                value={crud.values.status}
                                onChange={(event) =>
                                    crud.setValues({ ...crud.values, status: event.target.value as ProjectValues['status'] })
                                }
                            >
                                {statuses.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field label="Fecha de vencimiento" error={crud.errors.due_date}>
                            <input
                                type="date"
                                className="input w-full"
                                value={crud.values.due_date}
                                onChange={(event) => crud.setValues({ ...crud.values, due_date: event.target.value })}
                            />
                        </Field>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
