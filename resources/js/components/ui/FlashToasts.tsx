import { useFlash } from '@/hooks/use-flash';

export function FlashToasts() {
    const { toasts, dismiss } = useFlash();

    if (toasts.length === 0) {
        return null;
    }

    return (
        <div className="toast toast-end z-50">
            {toasts.map((toast) => (
                <button
                    key={`${toast.type}-${toast.message}`}
                    type="button"
                    className={`alert ${toast.type === 'success' ? 'alert-success' : 'alert-error'} cursor-pointer`}
                    onClick={dismiss}
                >
                    <i
                        className={`fa-solid ${toast.type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'}`}
                        aria-hidden="true"
                    />
                    <span>{toast.message}</span>
                </button>
            ))}
        </div>
    );
}
