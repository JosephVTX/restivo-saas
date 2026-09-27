import { z } from 'zod';

export const tenantSchema = z.object({
    name: z.string().min(1, 'Name is required').max(255),
    plan: z.string().max(100).optional().or(z.literal('')),
    status: z.enum(['active', 'trial', 'suspended', 'cancelled']),
    locale: z.enum(['en', 'es']),
});

export type TenantValues = z.infer<typeof tenantSchema>;

export const tenantAccessSchema = z.object({
    name: z.string().min(1, 'Name is required').max(255),
    email: z.string().min(1, 'Email is required').email('Invalid email'),
    password: z.string().min(8, 'At least 8 characters').optional().or(z.literal('')),
});

export type TenantAccessValues = z.infer<typeof tenantAccessSchema>;
