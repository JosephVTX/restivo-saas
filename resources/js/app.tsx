import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { SWRProvider } from '@/lib/swr';

const appName = import.meta.env.VITE_APP_NAME ?? 'Restivo';

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ) as never,
    setup({ el, App, props }) {
        createRoot(el).render(
            <SWRProvider>
                <App {...props} />
            </SWRProvider>,
        );
    },
    progress: {
        color: '#4f46e5',
    },
});
