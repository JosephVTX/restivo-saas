import { z } from 'zod';

export const identityDocumentTypes = ['ruc', 'dni', 'ce', 'passport', 'other'] as const;

export const customerSchema = z
    .object({
        doc_type: z.enum(identityDocumentTypes),
        doc_number: z.string().max(20).optional().or(z.literal('')),
        name: z.string().min(1, 'El nombre es obligatorio').max(255),
        email: z.string().email('Correo electrónico inválido').max(255).optional().or(z.literal('')),
        phone: z.string().max(30).optional().or(z.literal('')),
        address: z.string().max(255).optional().or(z.literal('')),
        notes: z.string().max(255).optional().or(z.literal('')),
        is_active: z.boolean(),
    })
    .superRefine((values, ctx) => {
        if (values.doc_type === 'ruc' && values.doc_number && !/^\d{11}$/.test(values.doc_number)) {
            ctx.addIssue({ code: 'custom', path: ['doc_number'], message: 'El RUC debe tener 11 dígitos.' });
        }

        if (values.doc_type === 'dni' && values.doc_number && !/^\d{8}$/.test(values.doc_number)) {
            ctx.addIssue({ code: 'custom', path: ['doc_number'], message: 'El DNI debe tener 8 dígitos.' });
        }
    });

export type CustomerValues = z.infer<typeof customerSchema>;
