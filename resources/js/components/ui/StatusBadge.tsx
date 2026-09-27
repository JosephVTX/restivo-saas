import { statusLabel } from '@/lib/labels';
import { cn } from '@/lib/utils';

const colors: Record<string, string> = {
    // Tenant / project
    draft: 'badge-ghost',
    active: 'badge-success',
    trial: 'badge-info',
    archived: 'badge-neutral',
    suspended: 'badge-error',
    cancelled: 'badge-error',

    // Mesas
    available: 'badge-success',
    occupied: 'badge-warning',
    billing: 'badge-info',
    reserved: 'badge-neutral',
    cleaning: 'badge-ghost',

    // Pedidos / comandas
    open: 'badge-warning',
    sent: 'badge-info',
    served: 'badge-success',
    paid: 'badge-success',

    pending: 'badge-warning',
    preparing: 'badge-info',
    ready: 'badge-success',
    delivered: 'badge-success',
    void: 'badge-error',

    // Caja / comprobantes
    closed: 'badge-neutral',
    issued: 'badge-info',
    accepted: 'badge-success',
    rejected: 'badge-error',
    annulled: 'badge-error',
    none: 'badge-ghost',
};

export function StatusBadge({ status, label }: { status: string; label?: string }) {
    return (
        <span className={cn('badge badge-sm whitespace-nowrap', colors[status] ?? 'badge-ghost')}>
            {label ?? statusLabel(status)}
        </span>
    );
}
