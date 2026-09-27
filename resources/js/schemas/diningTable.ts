import { z } from 'zod';

export const tableStatuses = ['available', 'occupied', 'billing', 'reserved', 'cleaning'] as const;

export const diningTableSchema = z.object({
    zone_id: z
        .string()
        .nullish()
        .transform((value) => (value ? value : null)),
    name: z.string().min(1, 'El nombre es obligatorio').max(255),
    capacity: z.coerce.number().int().min(1, 'La capacidad mínima es 1'),
    status: z.enum(tableStatuses),
    sort_order: z.coerce.number().int().min(0),
    is_active: z.boolean(),
});

export type DiningTableValues = z.infer<typeof diningTableSchema>;
