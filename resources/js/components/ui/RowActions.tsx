import type { ReactNode } from 'react';

export function RowActions({
    onEdit,
    onDelete,
    extra,
}: {
    onEdit?: () => void;
    onDelete?: () => void;
    extra?: ReactNode;
}) {
    return (
        <div className="flex justify-end gap-0.5">
            {extra}
            {onEdit ? (
                <button type="button" className="btn btn-ghost btn-xs" title="Editar" onClick={onEdit}>
                    <i className="fa-solid fa-pen" aria-hidden="true" />
                </button>
            ) : null}
            {onDelete ? (
                <button type="button" className="btn btn-ghost btn-xs text-error" title="Eliminar" onClick={onDelete}>
                    <i className="fa-solid fa-trash" aria-hidden="true" />
                </button>
            ) : null}
        </div>
    );
}
