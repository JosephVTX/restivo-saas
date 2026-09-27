import { useState } from 'react';
import type { ZodType } from 'zod';
import { confirmDelete } from '@/components/ui/Modal';
import { api, validationErrors } from '@/lib/http';

interface Entity {
    uuid: string;
}

/**
 * Generic create/update/delete state for a modal-based resource table.
 *
 * Removes the repeated modal + zod + axios + SWR-mutate plumbing so pages
 * only declare their endpoint, schema, empty values and how to map a row to
 * form values.
 */
export function useCrud<TValues, TEntity extends Entity>({
    endpoint,
    schema,
    empty,
    toValues,
    mutate,
    removeLabel,
    afterSave,
}: {
    endpoint: string;
    schema: ZodType<TValues>;
    empty: TValues;
    toValues?: (entity: TEntity) => TValues;
    mutate?: () => Promise<unknown> | void;
    removeLabel?: (entity: TEntity) => string;
    afterSave?: (entity: TEntity) => Promise<void> | void;
}) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<TEntity | null>(null);
    const [values, setValues] = useState<TValues>(empty);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);

    const openCreate = () => {
        setEditing(null);
        setValues(empty);
        setErrors({});
        setOpen(true);
    };

    const openEdit = (entity: TEntity) => {
        setEditing(entity);
        setValues(toValues ? toValues(entity) : (entity as unknown as TValues));
        setErrors({});
        setOpen(true);
    };

    const close = () => setOpen(false);

    const save = async (): Promise<TEntity | null> => {
        const parsed = schema.safeParse(values);

        if (!parsed.success) {
            setErrors(Object.fromEntries(parsed.error.issues.map((issue) => [String(issue.path[0]), issue.message])));

            return null;
        }

        setSaving(true);
        setErrors({});

        try {
            const body = editing
                ? await api.patch<TEntity>(`${endpoint}/${editing.uuid}`, parsed.data)
                : await api.post<TEntity>(endpoint, parsed.data);

            const entity = (body as { data?: TEntity }).data ?? body;

            await afterSave?.(entity);
            await mutate?.();
            setOpen(false);

            return entity;
        } catch (error) {
            const fieldErrors = validationErrors(error);
            const message = (error as { response?: { data?: { message?: string } } })?.response?.data?.message;

            setErrors(message && Object.keys(fieldErrors).length === 0 ? { message } : fieldErrors);

            return null;
        } finally {
            setSaving(false);
        }
    };

    const remove = async (entity: TEntity) => {
        const message = removeLabel
            ? removeLabel(entity)
            : `¿Eliminar "${(entity as { name?: string }).name ?? 'registro'}"?`;

        if (!confirmDelete(message)) {
            return;
        }

        await api.delete(`${endpoint}/${entity.uuid}`);
        await mutate?.();
    };

    return {
        open,
        editing,
        values,
        setValues,
        errors,
        saving,
        openCreate,
        openEdit,
        close,
        save,
        remove,
    };
}
