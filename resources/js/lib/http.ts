import type { AxiosRequestConfig } from 'axios';
import { http } from './axios';

/**
 * SWR fetcher. Every endpoint returns either `{ data }` or `{ data, meta }`.
 */
export async function fetcher<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const { data } = await http.get<T>(url, config);

    return data;
}

export const api = {
    get: <T>(url: string, config?: AxiosRequestConfig): Promise<T> =>
        http.get<T>(url, config).then((response) => response.data),
    post: <T>(url: string, body?: unknown, config?: AxiosRequestConfig): Promise<T> =>
        http.post<T>(url, body, config).then((response) => response.data),
    patch: <T>(url: string, body?: unknown): Promise<T> =>
        http.patch<T>(url, body).then((response) => response.data),
    delete: <T>(url: string): Promise<T> =>
        http.delete<T>(url).then((response) => response.data),
};

/**
 * Extracts field errors from a Laravel 422 response.
 */
export function validationErrors(error: unknown): Record<string, string> {
    const response = (error as { response?: { status?: number; data?: { errors?: Record<string, string[]> } } })
        ?.response;

    if (response?.status === 422 && response.data?.errors) {
        return Object.fromEntries(
            Object.entries(response.data.errors).map(([key, messages]) => [key, messages[0]]),
        );
    }

    return {};
}
