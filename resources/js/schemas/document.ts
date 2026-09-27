import { z } from 'zod';

export const documentTypes = ['nota_venta', 'boleta', 'factura'] as const;
export const identityDocumentTypes = ['ruc', 'dni', 'ce', 'passport', 'other'] as const;

export const issueDocumentSchema = z
    .object({
        type: z.enum(documentTypes),
        series: z.string().max(10).optional().or(z.literal('')),
        customer_id: z.string().optional().or(z.literal('')),
        customer_doc_type: z.enum(identityDocumentTypes).optional().or(z.literal('')),
        customer_doc_number: z.string().max(20).optional().or(z.literal('')),
        customer_name: z.string().max(255).optional().or(z.literal('')),
        customer_address: z.string().max(255).optional().or(z.literal('')),
        notes: z.string().max(255).optional().or(z.literal('')),
    })
    .superRefine((values, ctx) => {
        if (values.type === 'factura') {
            if (values.customer_doc_type !== 'ruc') {
                ctx.addIssue({
                    code: 'custom',
                    path: ['customer_doc_type'],
                    message: 'La factura requiere un RUC.',
                });
            }

            if (!/^\d{11}$/.test(values.customer_doc_number ?? '')) {
                ctx.addIssue({
                    code: 'custom',
                    path: ['customer_doc_number'],
                    message: 'El RUC debe tener 11 dígitos.',
                });
            }
        }

        if (values.type === 'boleta' && values.customer_doc_number) {
            if (!/^(\d{8}|\d{11})$/.test(values.customer_doc_number)) {
                ctx.addIssue({
                    code: 'custom',
                    path: ['customer_doc_number'],
                    message: 'El documento debe tener 8 u 11 dígitos.',
                });
            }
        }
    });

export type IssueDocumentValues = z.infer<typeof issueDocumentSchema>;
