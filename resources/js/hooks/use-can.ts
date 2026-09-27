import { useShared } from '@/hooks/use-shared';

/**
 * Permission helper backed by the `auth.permissions` shared prop. Returns a
 * predicate so callers can gate nav items, actions or pages consistently.
 */
export function useCan(): (permission: string) => boolean {
    const { auth } = useShared();
    const permissions = auth?.permissions ?? [];

    return (permission: string): boolean => permissions.includes(permission);
}
