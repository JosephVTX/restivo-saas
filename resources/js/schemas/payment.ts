import { z } from 'zod';

export const paymentMethods = ['cash', 'yape', 'plin', 'card', 'transfer', 'other'] as const;

export const paymentSchema = z.object({
    method: z.enum(paymentMethods),
    amount: z.coerce.number().min(0.01, 'Ingresa un monto mayor a cero'),
    tip: z.coerce.number().min(0, 'La propina no puede ser negativa').optional(),
    received_amount: z.coerce.number().min(0, 'El monto recibido no puede ser negativo').optional(),
    reference: z.string().max(255).optional().or(z.literal('')),
    notes: z.string().max(255).optional().or(z.literal('')),
});

export type PaymentValues = z.infer<typeof paymentSchema>;
