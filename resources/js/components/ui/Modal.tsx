import type { ReactNode } from 'react';

export function Modal({
    open,
    title,
    description,
    onClose,
    children,
    footer,
}: {
    open: boolean;
    title: string;
    description?: string;
    onClose: () => void;
    children: ReactNode;
    footer?: ReactNode;
}) {
    if (!open) {
        return null;
    }

    return (
        <div className="modal modal-open modal-bottom sm:modal-middle">
            <div className="modal-box">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h3 className="text-lg font-semibold">{title}</h3>
                        {description ? <p className="mt-1 text-sm opacity-70">{description}</p> : null}
                    </div>
                    <button
                        type="button"
                        className="btn btn-circle btn-ghost btn-sm"
                        onClick={onClose}
                        aria-label="Cerrar"
                    >
                        <i className="fa-solid fa-xmark" aria-hidden="true" />
                    </button>
                </div>
                <div className="mt-4">{children}</div>
                {footer ? <div className="modal-action">{footer}</div> : null}
            </div>
            <button type="button" className="modal-backdrop" onClick={onClose} aria-label="Cerrar" />
        </div>
    );
}

/**
 * Confirmation helper for destructive actions.
 */
export function confirmDelete(message: string): boolean {
    return window.confirm(message);
}
