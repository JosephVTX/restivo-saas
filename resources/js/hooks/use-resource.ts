import useSWR from 'swr';
import { buildQuery } from '@/lib/query';
import type { Paginated } from '@/types';

type QueryValue = string | number | boolean | undefined | null;

export interface ResourceParams {
    page?: number;
    per_page?: number;
    sort?: string;
    filter?: Record<string, QueryValue>;
}

/**
 * Generic SWR list hook for any `/api/v1/*` collection that follows the
 * `ApiController` + spatie QueryBuilder contract. Reuse this instead of writing
 * a per-resource hook — pass the endpoint and (optional) query params.
 */
export function useResource<T>(endpoint: string, params: ResourceParams = {}) {
    const { data, error, isLoading, mutate } = useSWR<Paginated<T>>(`${endpoint}${buildQuery(params)}`);

    return {
        items: data?.data ?? [],
        meta: data?.meta,
        error,
        isLoading,
        mutate,
    };
}
