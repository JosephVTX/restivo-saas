import { useShared } from '@/hooks/use-shared';
import { formatDate } from '@/lib/utils';

/**
 * Non-intrusive but always-present notice that tells the client when their
 * service period is about to end (or has already ended).
 */
export function ExpiryNotice() {
    const { tenant } = useShared();

    if (!tenant || (!tenant.has_expired && !tenant.is_expiring_soon)) {
        return null;
    }

    const subject = tenant.status === 'trial' ? 'Tu periodo de prueba' : 'Tu plan';

    if (tenant.has_expired) {
        return (
            <div role="alert" className="alert alert-error mb-4 items-start gap-3 rounded-box py-3">
                <i className="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true" />
                <div>
                    <p className="font-medium">{subject} ha vencido.</p>
                    <p className="text-sm opacity-90">
                        El servicio quedará deshabilitado. Contacta con el proveedor para renovar y seguir operando.
                    </p>
                </div>
            </div>
        );
    }

    const days = tenant.days_until_expiry ?? 0;
    const remaining = days <= 0 ? 'termina hoy' : days === 1 ? 'termina mañana' : `termina en ${days} días`;

    return (
        <div role="alert" className="alert alert-warning mb-4 items-start gap-3 rounded-box py-3">
            <i className="fa-solid fa-hourglass-half mt-0.5" aria-hidden="true" />
            <div>
                <p className="font-medium">
                    {subject} {remaining}.
                </p>
                <p className="text-sm opacity-90">
                    Se deshabilitará el {formatDate(tenant.expires_at)}. Renueva con tu proveedor para continuar sin
                    interrupciones.
                </p>
            </div>
        </div>
    );
}
