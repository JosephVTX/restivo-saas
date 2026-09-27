import { useFlash } from '@/hooks/use-flash';
import { useToasts } from '@/hooks/use-toasts';
import type { ToastType } from '@/lib/toast';

const styles: Record<ToastType, { cls: string; icon: string }> = {
    success: { cls: 'alert-success', icon: 'fa-circle-check' },
    error: { cls: 'alert-error', icon: 'fa-circle-exclamation' },
    info: { cls: 'alert-info', icon: 'fa-circle-info' },
};

/**
 * Global toast stack: Inertia flash messages plus imperative `toast.*` calls.
 */
export function FlashToasts() {
    const { toasts: flashToasts, dismiss: dismissFlash } = useFlash();
    const { toasts: pushed, dismiss: dismissPushed } = useToasts();

    const entries = [
        ...flashToasts.map((toast) => ({
            key: `flash-${toast.type}-${toast.message}`,
            type: toast.type as ToastType,
            message: toast.message,
            onClose: dismissFlash,
        })),
        ...pushed.map((toast) => ({
            key: `toast-${toast.id}`,
            type: toast.type,
            message: toast.message,
            onClose: () => dismissPushed(toast.id),
        })),
    ];

    if (entries.length === 0) {
        return null;
    }

    return (
        <div className="toast toast-end toast-top z-[60]" role="status" aria-live="polite">
            {entries.map((entry) => {
                const style = styles[entry.type];

                return (
                    <div key={entry.key} className={`alert ${style.cls} pointer-events-auto shadow-lg`}>
                        <i className={`fa-solid ${style.icon}`} aria-hidden="true" />
                        <span>{entry.message}</span>
                        <button type="button" className="btn btn-ghost btn-xs" onClick={entry.onClose} aria-label="Cerrar">
                            <i className="fa-solid fa-xmark" aria-hidden="true" />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}
