import { z } from 'zod';

export const orderTypes = ['dine_in', 'takeaway', 'delivery'] as const;

export const orderSchema = z.object({
    type: z.enum(orderTypes),
    guests: z.coerce.number().int().min(1, 'Mínimo 1 persona').max(50, 'Máximo 50 personas'),
    notes: z.string().max(255).optional().or(z.literal('')),
});

export const orderItemSchema = z.object({
    product: z.string().min(1, 'Selecciona un producto'),
    quantity: z.coerce.number().min(0.5, 'La cantidad mínima es 0.5').max(99, 'La cantidad máxima es 99'),
    notes: z.string().max(255).optional().or(z.literal('')),
    modifiers: z.array(z.string()),
});

export type OrderValues = z.infer<typeof orderSchema>;
export type OrderItemValues = z.infer<typeof orderItemSchema>;
