type QueryValue = string | number | boolean | undefined | null;

/**
 * Serialize list parameters to the spatie/laravel-query-builder contract:
 * `?page=1&per_page=15&sort=-created_at&filter[search]=foo&filter[status]=active`.
 */
export function buildQuery(
    params: {
        page?: number;
        per_page?: number;
        sort?: string;
        filter?: Record<string, QueryValue>;
    } = {},
): string {
    const search = new URLSearchParams();

    if (params.page) {
        search.set('page', String(params.page));
    }

    if (params.per_page) {
        search.set('per_page', String(params.per_page));
    }

    if (params.sort) {
        search.set('sort', params.sort);
    }

    Object.entries(params.filter ?? {}).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            search.set(`filter[${key}]`, String(value));
        }
    });

    const query = search.toString();

    return query ? `?${query}` : '';
}
