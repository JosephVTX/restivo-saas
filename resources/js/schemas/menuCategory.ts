import { z } from 'zod';

export const stations = ['kitchen', 'bar', 'none'] as const;

export const menuCategorySchema = z.object({
    name: z.string().min(1, 'El nombre es obligatorio').max(255),
    description: z.string().max(255).optional().or(z.literal('')),
    station: z.enum(stations),
    color: z.string().max(20).optional().or(z.literal('')),
    sort_order: z.coerce.number().int().min(0),
    is_active: z.boolean(),
});

export type MenuCategoryValues = z.infer<typeof menuCategorySchema>;
