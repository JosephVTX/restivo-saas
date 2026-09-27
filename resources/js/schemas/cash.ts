import { z } from 'zod';

export const openCashSessionSchema = z.object({
    opening_amount: z.coerce.number().min(0, 'El monto no puede ser negativo'),
    notes: z.string().max(255).optional().or(z.literal('')),
});

export const closeCashSessionSchema = z.object({
    closing_amount: z.coerce.number().min(0, 'El monto no puede ser negativo'),
    notes: z.string().max(255).optional().or(z.literal('')),
});

export const cashMovementSchema = z.object({
    type: z.enum(['in', 'out']),
    amount: z.coerce.number().min(0.01, 'Ingresa un monto mayor a cero'),
    concept: z.string().min(1, 'Ingresa un concepto').max(255),
    notes: z.string().max(255).optional().or(z.literal('')),
});

export type OpenCashSessionValues = z.infer<typeof openCashSessionSchema>;
export type CloseCashSessionValues = z.infer<typeof closeCashSessionSchema>;
export type CashMovementValues = z.infer<typeof cashMovementSchema>;
