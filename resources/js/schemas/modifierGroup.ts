import { z } from 'zod';

export const selectionTypes = ['single', 'multiple'] as const;

export const modifierSchema = z.object({
    uuid: z.string().optional(),
    name: z.string().min(1, 'El nombre es obligatorio').max(255),
    price: z.coerce.number().min(0, 'El precio no puede ser negativo'),
    is_default: z.boolean(),
    sort_order: z.coerce.number().int().min(0),
    is_active: z.boolean(),
});

export const modifierGroupSchema = z.object({
    name: z.string().min(1, 'El nombre es obligatorio').max(255),
    selection_type: z.enum(selectionTypes),
    is_required: z.boolean(),
    min_selections: z.coerce.number().int().min(0),
    max_selections: z.coerce.number().int().min(0),
    sort_order: z.coerce.number().int().min(0),
    is_active: z.boolean(),
    modifiers: z.array(modifierSchema),
});

export type ModifierValues = z.infer<typeof modifierSchema>;
export type ModifierGroupValues = z.infer<typeof modifierGroupSchema>;
