import { z } from 'zod';

export const zoneSchema = z.object({
    name: z.string().min(1, 'El nombre es obligatorio').max(255),
    sort_order: z.coerce.number().int().min(0),
    is_active: z.boolean(),
});

export type ZoneValues = z.infer<typeof zoneSchema>;
