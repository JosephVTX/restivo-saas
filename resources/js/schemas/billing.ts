import { z } from 'zod';

export const billingSchema = z.object({
    enabled: z.boolean(),
    ruc: z
        .string()
        .regex(/^\d{11}$/, 'El RUC debe tener 11 dígitos')
        .optional()
        .or(z.literal('')),
    business_name: z.string().max(255).optional().or(z.literal('')),
    trade_name: z.string().max(255).optional().or(z.literal('')),
    address: z.string().max(255).optional().or(z.literal('')),
    ubigeo: z
        .string()
        .regex(/^\d{6}$/, 'El ubigeo debe tener 6 dígitos')
        .optional()
        .or(z.literal('')),
    email: z.string().max(255).optional().or(z.literal('')),
    phone: z.string().max(30).optional().or(z.literal('')),
    sol_user: z.string().max(100).optional().or(z.literal('')),
    sol_password: z.string().max(255).optional().or(z.literal('')),
    mode: z.enum(['beta', 'production']),
    boleta_series: z.string().max(10).optional().or(z.literal('')),
    factura_series: z.string().max(10).optional().or(z.literal('')),
    legend: z.string().max(255).optional().or(z.literal('')),
    igv_rate: z.coerce.number().min(0).max(1),
});

export type BillingValues = z.infer<typeof billingSchema>;
