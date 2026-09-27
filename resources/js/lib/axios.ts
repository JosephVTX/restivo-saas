import { create } from 'axios';
import { toast } from './toast';

/**
 * Shared axios instance for the SPA.
 *
 * Same-origin, cookie based: Laravel sets the XSRF-TOKEN cookie and axios
 * automatically echoes it back as the X-XSRF-TOKEN header, so CSRF works
 * without extra wiring. `Accept: application/json` makes Laravel return JSON
 * validation errors (422) instead of redirects.
 */
export const http = create({
    baseURL: '/',
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
});

http.interceptors.response.use(
    (response) => response,
    (error: unknown) => {
        const status = (error as { response?: { status?: number } })?.response?.status;

        if (status === 401 && !window.location.pathname.startsWith('/login')) {
            window.location.href = '/login';
        }

        // Surface failures the page cannot render inline (422 keeps its field
        // errors, and 401 already redirects).
        if (status === 403) {
            toast.error('No tienes permiso para realizar esta acción.');
        } else if (status === 429) {
            toast.error('Demasiadas solicitudes. Espera un momento.');
        } else if (status !== undefined && status >= 500) {
            toast.error('Ocurrió un error inesperado. Inténtalo de nuevo.');
        }

        return Promise.reject(error);
    },
);
