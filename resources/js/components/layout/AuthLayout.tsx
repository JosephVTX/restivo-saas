import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { FlashToasts } from '@/components/ui/FlashToasts';
import { ThemeToggle } from '@/components/ui/ThemeToggle';
import { useShared } from '@/hooks/use-shared';

const highlights = [
    {
        icon: 'fa-bolt',
        title: 'Pedidos en segundos',
        text: 'El mozo toma la orden desde su celular con botones grandes.',
    },
    {
        icon: 'fa-fire-burner',
        title: 'Cocina sin papeles',
        text: 'Las comandas avanzan en pantalla: cola, preparación y listo.',
    },
    {
        icon: 'fa-cash-register',
        title: 'Caja bajo control',
        text: 'Pagos, propinas, vuelto y arqueo del turno automáticos.',
    },
    {
        icon: 'fa-file-invoice-dollar',
        title: 'Facturación electrónica',
        text: 'Boleta y factura ante SUNAT, configurable por restaurante.',
    },
];

export default function AuthLayout({
    title,
    subtitle,
    children,
}: {
    title: string;
    subtitle?: string;
    children: ReactNode;
}) {
    const { app } = useShared();

    return (
        <div className="grid min-h-screen bg-base-200 lg:grid-cols-2">
            <section className="relative hidden flex-col justify-between overflow-hidden bg-primary p-12 text-primary-content lg:flex">
                <div
                    className="pointer-events-none absolute inset-0 opacity-25"
                    style={{
                        backgroundImage:
                            'radial-gradient(circle at 15% 15%, white 0, transparent 32%), radial-gradient(circle at 85% 70%, white 0, transparent 28%)',
                    }}
                />

                <Link href="/" className="relative flex items-center gap-2 text-lg font-semibold">
                    <span className="grid h-9 w-9 place-items-center rounded-lg bg-primary-content text-primary">
                        <i className="fa-solid fa-utensils text-sm" aria-hidden="true" />
                    </span>
                    {app.name}
                </Link>

                <div className="relative max-w-md">
                    <h2 className="text-3xl leading-tight font-bold">
                        Todo tu restaurante, en un solo sistema.
                    </h2>
                    <p className="mt-3 opacity-80">
                        Salón, carta, pedidos, cocina, caja y facturación electrónica. Rápido para el mozo,
                        simple para el dueño.
                    </p>

                    <ul className="mt-8 space-y-4">
                        {highlights.map((highlight) => (
                            <li key={highlight.title} className="flex items-start gap-3">
                                <span className="mt-0.5 grid h-8 w-8 flex-none place-items-center rounded-lg bg-primary-content/15">
                                    <i className={`fa-solid ${highlight.icon} text-sm`} aria-hidden="true" />
                                </span>
                                <span>
                                    <span className="block font-medium">{highlight.title}</span>
                                    <span className="block text-sm opacity-75">{highlight.text}</span>
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>

                <p className="relative text-xs opacity-60">
                    © {new Date().getFullYear()} {app.name}
                </p>
            </section>

            <section className="flex flex-col p-4 sm:p-8">
                <div className="flex items-center justify-between">
                    <Link href="/" className="flex items-center gap-2 font-semibold lg:hidden">
                        <span className="grid h-8 w-8 place-items-center rounded-lg bg-primary text-primary-content">
                            <i className="fa-solid fa-utensils text-sm" aria-hidden="true" />
                        </span>
                        {app.name}
                    </Link>
                    <div className="ml-auto">
                        <ThemeToggle />
                    </div>
                </div>

                <div className="flex flex-1 items-center justify-center py-8">
                    <div className="w-full max-w-md">
                        <div className="card border border-base-300 bg-base-100 shadow-sm">
                            <div className="card-body gap-5">
                                <div>
                                    <h1 className="text-xl font-semibold">{title}</h1>
                                    {subtitle ? <p className="mt-1 text-sm opacity-70">{subtitle}</p> : null}
                                </div>
                                {children}
                            </div>
                        </div>

                        <p className="mt-4 text-center text-xs opacity-50 lg:hidden">
                            © {new Date().getFullYear()} {app.name}
                        </p>
                    </div>
                </div>
            </section>

            <FlashToasts />
        </div>
    );
}
