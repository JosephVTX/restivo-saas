import { z } from 'zod';

export const projectSchema = z.object({
    name: z.string().min(1, 'Name is required').max(255),
    description: z.string().max(5000).optional().or(z.literal('')),
    status: z.enum(['draft', 'active', 'archived']),
    due_date: z.string().optional().or(z.literal('')),
});

export type ProjectValues = z.infer<typeof projectSchema>;
