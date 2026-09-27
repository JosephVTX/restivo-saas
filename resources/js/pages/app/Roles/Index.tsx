import { Head } from '@inertiajs/react';
import AppLayout from '@/components/layout/AppLayout';
import { PageHeader } from '@/components/ui/PageHeader';
import { useResource } from '@/hooks/use-resource';
import type { Role } from '@/types';

export default function RolesIndex() {
    const { items: roles, isLoading } = useResource<Role>('/api/v1/roles');

    return (
        <AppLayout title="Roles">
            <Head title="Roles" />
            <PageHeader title="Roles y permisos" description="Permisos de spatie/laravel-permission para este espacio." />

            <div className="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {isLoading ? (
                    <div className="col-span-full flex justify-center py-16">
                        <span className="loading loading-spinner" />
                    </div>
                ) : (
                    roles.map((role) => (
                        <div key={role.id} className="card border border-base-300 bg-base-100">
                            <div className="card-body">
                                <h2 className="card-title text-base">
                                    <i className="fa-solid fa-user-shield text-primary" aria-hidden="true" />
                                    {role.label}
                                </h2>
                                <div className="mt-2 flex flex-wrap gap-1">
                                    {role.permissions?.map((permission) => (
                                        <span key={permission.name} className="badge badge-outline badge-sm">
                                            {permission.label}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </AppLayout>
    );
}
