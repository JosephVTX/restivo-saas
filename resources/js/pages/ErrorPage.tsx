import { Link } from '@inertiajs/react';
import { ThemeToggle } from '@/components/ui/ThemeToggle';
import { useShared } from '@/hooks/use-shared';

interface ErrorMeta {
    title: string;
    description: string;
    icon: string;
}

const errors: Record<number, ErrorMeta> = {
    403: {
        title: 'Acceso restringido',
        description: 'No tienes permiso para ver esta página. Pide a un administrador que revise tu rol.',
        icon: 'fa-lock',
    },
    404: {
        title: 'Página no encontrada',
        description: 'La página que buscas no existe o fue movida. Revisa la dirección o vuelve al inicio.',
        icon: 'fa-compass',
    },
    419: {
        title: 'Tu sesión expiró',
        description: 'Por seguridad cerramos la sesión. Vuelve a iniciar sesión para continuar.',
        icon: 'fa-hourglass-half',
    },
    429: {
        title: 'Demasiadas solicitudes',
        description: 'Hiciste muchas solicitudes en poco tiempo. Espera un momento e inténtalo de nuevo.',
        icon: 'fa-gauge-high',
    },
    500: {
        title: 'Algo salió mal',
        description: 'Tuvimos un problema al procesar tu solicitud. Reintenta en unos segundos.',
        icon: 'fa-triangle-exclamation',
    },
    503: {
        title: 'En mantenimiento',
        description: 'Estamos mejorando el sistema. Vuelve en unos minutos.',
        icon: 'fa-screwdriver-wrench',
    },
};

const fallback: ErrorMeta = {
    title: 'Ocurrió un problema',
    description: 'No pudimos mostrar esta página. Vuelve al inicio e inténtalo de nuevo.',
    icon: 'fa-circle-exclamation',
};

export default function ErrorPage({ status }: { status: number }) {
    const { app } = useShared();
    const meta = errors[status] ?? fallback;

    return (
        <div className="relative grid min-h-screen place-items-center overflow-hidden bg-base-200 p-6">
            <div
                className="pointer-events-none absolute inset-0 opacity-[0.07]"
                style={{
                    backgroundImage:
                        'radial-gradient(circle at 20% 20%, var(--color-primary) 0, transparent 35%), radial-gradient(circle at 80% 75%, var(--color-primary) 0, transparent 30%)',
                }}
            />

            <div className="absolute top-4 right-4">
                <ThemeToggle />
            </div>

            <div className="relative w-full max-w-lg text-center">
                <Link href="/" className="inline-flex items-center gap-2 font-semibold">
                    <span className="grid h-9 w-9 place-items-center rounded-lg bg-primary text-primary-content">
                        <i className="fa-solid fa-utensils text-sm" aria-hidden="true" />
                    </span>
                    {app?.name ?? 'Restivo'}
                </Link>

                <div className="card mt-6 border border-base-300 bg-base-100 shadow-sm">
                    <div className="card-body items-center gap-5 py-12">
                        <span className="relative grid h-24 w-24 place-items-center rounded-full bg-primary/10 text-primary">
                            <i className={`fa-solid ${meta.icon} text-4xl`} aria-hidden="true" />
                            <span className="badge badge-primary absolute -right-1 -bottom-1 font-mono font-semibold">
                                {status}
                            </span>
                        </span>

                        <div className="space-y-2">
                            <h1 className="text-2xl font-bold text-base-content">{meta.title}</h1>
                            <p className="mx-auto max-w-sm text-base-content/70">{meta.description}</p>
                        </div>

                        <div className="mt-2 flex flex-wrap justify-center gap-2">
                            <Link href="/" className="btn btn-primary">
                                <i className="fa-solid fa-house" aria-hidden="true" />
                                Ir al inicio
                            </Link>
                            <button
                                type="button"
                                className="btn btn-ghost"
                                onClick={() => window.history.back()}
                            >
                                <i className="fa-solid fa-arrow-left" aria-hidden="true" />
                                Volver
                            </button>
                        </div>
                    </div>
                </div>

                <p className="mt-4 text-xs text-base-content/50">
                    © {new Date().getFullYear()} {app?.name ?? 'Restivo'}
                </p>
            </div>
        </div>
    );
}
