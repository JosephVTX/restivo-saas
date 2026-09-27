import { Head } from '@inertiajs/react';
import AdminLayout from '@/components/layout/AdminLayout';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { SearchInput } from '@/components/ui/SearchInput';
import { TableShell } from '@/components/ui/TableShell';
import { useDebouncedSearch } from '@/hooks/use-debounced-search';
import { useResource } from '@/hooks/use-resource';
import type { User } from '@/types';
import { formatDate } from '@/lib/utils';

export default function Users() {
    const { search, query, change, page, setPage } = useDebouncedSearch();
    const { items: users, meta, isLoading } = useResource<User>('/api/v1/admin/users', {
        page,
        filter: { search: query },
    });

    return (
        <AdminLayout title="Usuarios">
            <Head title="Usuarios" />
            <PageHeader title="Usuarios" description="Todas las cuentas de la plataforma." />

            <div className="mt-4">
                <SearchInput value={search} onChange={change} placeholder="Buscar por nombre o correo…" />
            </div>

            <div className="mt-4">
                <TableShell
                    head={['Nombre', 'Correo', 'Tipo', 'Espacios', 'Último acceso']}
                    isLoading={isLoading}
                    isEmpty={users.length === 0}
                    empty={<EmptyState icon="fa-users" title="Sin usuarios" />}
                >
                    {users.map((user) => (
                        <tr key={user.uuid} className="hover">
                            <td className="font-medium">{user.name}</td>
                            <td className="opacity-80">{user.email}</td>
                            <td>
                                {user.is_super_admin ? (
                                    <span className="badge badge-primary badge-sm">Super administrador</span>
                                ) : (
                                    <span className="badge badge-ghost badge-sm">Miembro</span>
                                )}
                            </td>
                            <td>{user.memberships_count ?? 0}</td>
                            <td className="text-sm opacity-70">{formatDate(user.last_login_at)}</td>
                        </tr>
                    ))}
                </TableShell>
            </div>

            <div className="mt-4">
                <Pagination meta={meta} onChange={setPage} />
            </div>
        </AdminLayout>
    );
}
