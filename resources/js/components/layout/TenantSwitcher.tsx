import { router } from '@inertiajs/react';
import { useShared } from '@/hooks/use-shared';
import { cn } from '@/lib/utils';

function switchTo(uuid: string) {
    router.post('/tenant/switch', { tenant: uuid }, { preserveScroll: true });
}

export function TenantSwitcher() {
    const { tenants, tenant } = useShared();

    if (tenants.length === 0) {
        return null;
    }

    return (
        <div className="dropdown dropdown-end">
            <button type="button" tabIndex={0} className="btn btn-ghost btn-sm gap-2">
                <i className="fa-solid fa-building text-primary" aria-hidden="true" />
                <span className="hidden max-w-40 truncate sm:inline">{tenant?.name ?? 'Espacio de trabajo'}</span>
                <i className="fa-solid fa-chevron-down text-[10px] opacity-60" aria-hidden="true" />
            </button>
            <ul tabIndex={0} className="dropdown-content menu z-40 mt-2 w-56 rounded-box bg-base-100 p-2 shadow-lg">
                <li className="menu-title text-xs">Cambiar espacio</li>
                {tenants.map((item) => (
                    <li key={item.uuid}>
                        <button
                            type="button"
                            className={cn(item.uuid === tenant?.uuid && 'active')}
                            onClick={() => switchTo(item.uuid)}
                        >
                            {item.name}
                        </button>
                    </li>
                ))}
            </ul>
        </div>
    );
}
