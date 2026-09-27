import { z } from 'zod';

export const memberSchema = z.object({
    email: z.string().min(1, 'Email is required').email('Invalid email'),
    role: z.enum(['owner', 'admin', 'member']),
    job_title: z.string().max(255).optional().or(z.literal('')),
});

export type MemberValues = z.infer<typeof memberSchema>;
