import { z } from 'zod';
import { stations } from '@/schemas/menuCategory';

export const taxTypes = ['gravado', 'exonerado', 'inafecto'] as const;

const nullableNumber = z.preprocess(
    (value) => (value === '' || value === null || value === undefined ? null : value),
    z.coerce.number().min(0).nullable(),
);

export const productSchema = z.object({
    name: z.string().min(1, 'El nombre es obligatorio').max(255),
    description: z.string().max(255).optional().or(z.literal('')),
    sku: z.string().max(255).optional().or(z.literal('')),
    menu_category_id: z
        .string()
        .nullish()
        .transform((value) => (value ? value : null)),
    price: z.coerce.number().min(0, 'El precio no puede ser negativo'),
    cost: nullableNumber,
    tax_type: z.enum(taxTypes),
    station: z.enum(stations),
    unit: z.string().max(20),
    is_available: z.boolean(),
    track_stock: z.boolean(),
    stock: nullableNumber,
    sort_order: z.coerce.number().int().min(0),
    is_active: z.boolean(),
    modifier_groups: z.array(z.string()),
});

export type ProductValues = z.infer<typeof productSchema>;
