import { Head, Link } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import useSWR from 'swr';
import AppLayout from '@/components/layout/AppLayout';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { useCan } from '@/hooks/use-can';
import { fetcher } from '@/lib/http';
import { cn } from '@/lib/utils';
import type { DashboardReport, EnumOption } from '@/types';

interface Props {
    rangeOptions: EnumOption[];
}

function formatPrice(value: number | null | undefined): string {
    return `S/ ${Number(value ?? 0).toFixed(2)}`;
}

function Kpi({
    label,
    value,
    icon,
    hint,
}: {
    label: string;
    value: string;
    icon: string;
    hint?: ReactNode;
}) {
    return (
        <div className="card border border-base-300 bg-base-100">
            <div className="card-body gap-2">
                <div className="flex items-center justify-between">
                    <span className="text-sm opacity-70">{label}</span>
                    <span className="grid h-9 w-9 place-items-center rounded-lg bg-base-200 text-primary">
                        <i className={`fa-solid ${icon}`} aria-hidden="true" />
                    </span>
                </div>
                <span className="text-3xl font-semibold tabular-nums">{value}</span>
                {hint ? <div className="text-xs opacity-70">{hint}</div> : null}
            </div>
        </div>
    );
}

function KpiSkeleton() {
    return (
        <div className="card border border-base-300 bg-base-100">
            <div className="card-body gap-3">
                <div className="skeleton h-4 w-24" />
                <div className="skeleton h-8 w-32" />
            </div>
        </div>
    );
}

function ChangeBadge({ report }: { report: DashboardReport }) {
    const change = report.comparison.change_percentage;

    if (change === null) {
        return <span className="badge badge-ghost badge-sm">Sin datos previos</span>;
    }

    const positive = change >= 0;

    return (
        <span className={cn('badge badge-sm gap-1', positive ? 'badge-success' : 'badge-error')}>
            <i className={`fa-solid ${positive ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down'}`} aria-hidden="true" />
            {`${positive ? '+' : ''}${change.toFixed(1)}%`}
        </span>
    );
}

function PanelCard({ title, icon, children }: { title: string; icon: string; children: ReactNode }) {
    return (
        <div className="card border border-base-300 bg-base-100">
            <div className="card-body">
                <h2 className="card-title text-base">
                    <i className={`fa-solid ${icon} text-primary`} aria-hidden="true" />
                    {title}
                </h2>
                <div className="mt-2">{children}</div>
            </div>
        </div>
    );
}

export default function AppDashboard({ rangeOptions }: Props) {
    const can = useCan();
    const allowed = can('reports.view');

    const [period, setPeriod] = useState<string>(rangeOptions[0]?.value ?? 'today');

    const { data, error, isLoading } = useSWR<{ data: DashboardReport }>(
        allowed ? `/api/v1/reports/dashboard?period=${period}` : null,
        fetcher,
    );

    const report = data?.data;

    if (!allowed) {
        return (
            <AppLayout title="Panel">
                <Head title="Panel" />
                <PageHeader title="Panel" description="Un vistazo a tu restaurante." />

                <div className="mt-6 card border border-base-300 bg-base-100">
                    <div className="card-body items-start gap-4">
                        <h2 className="card-title">Bienvenido</h2>
                        <p className="text-sm opacity-70">
                            No tienes permiso para ver los reportes. Aquí tienes accesos rápidos para empezar.
                        </p>
                        <div className="flex flex-wrap gap-3">
                            <Link href="/app/tables" className="btn btn-primary">
                                <i className="fa-solid fa-chair" aria-hidden="true" /> Mesas
                            </Link>
                            <Link href="/app/pos" className="btn btn-outline">
                                <i className="fa-solid fa-cash-register" aria-hidden="true" /> POS
                            </Link>
                            <Link href="/app/kitchen" className="btn btn-outline">
                                <i className="fa-solid fa-fire-burner" aria-hidden="true" /> Cocina
                            </Link>
                        </div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    const maxHour = Math.max(1, ...(report?.sales_by_hour.map((entry) => entry.total) ?? [0]));

    return (
        <AppLayout title="Panel">
            <Head title="Panel" />

            <PageHeader
                title="Panel"
                description="Resumen de ventas y operación del restaurante."
                actions={
                    <div className="join">
                        {rangeOptions.map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                className={cn('btn join-item btn-sm', period === option.value && 'btn-active')}
                                onClick={() => setPeriod(option.value)}
                            >
                                {option.label}
                            </button>
                        ))}
                    </div>
                }
            />

            {error ? (
                <div className="mt-6 alert alert-error">
                    <i className="fa-solid fa-circle-exclamation" aria-hidden="true" />
                    <span>No se pudieron cargar los reportes. Intenta nuevamente.</span>
                </div>
            ) : null}

            {isLoading ? (
                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {Array.from({ length: 6 }).map((_, index) => (
                        <KpiSkeleton key={index} />
                    ))}
                </div>
            ) : report ? (
                <>
                    <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Kpi
                            label="Ventas"
                            value={formatPrice(report.sales.total)}
                            icon="fa-sack-dollar"
                            hint={<ChangeBadge report={report} />}
                        />
                        <Kpi
                            label="Pedidos"
                            value={report.sales.orders.toString()}
                            icon="fa-receipt"
                        />
                        <Kpi
                            label="Ticket promedio"
                            value={formatPrice(report.sales.average_ticket)}
                            icon="fa-tag"
                        />
                        <Kpi
                            label="Propinas"
                            value={formatPrice(report.sales.tips)}
                            icon="fa-hand-holding-dollar"
                        />
                        <Kpi
                            label="Pedidos abiertos"
                            value={report.open_orders.toString()}
                            icon="fa-clock"
                        />
                        <Kpi
                            label="Ocupación de mesas"
                            value={`${report.tables.occupancy_rate}%`}
                            icon="fa-chair"
                            hint={`${report.tables.occupied} de ${report.tables.total} mesas`}
                        />
                    </div>

                    <div className="mt-6">
                        <PanelCard title="Ventas por hora" icon="fa-chart-column">
                            <div className="flex h-40 items-end gap-1">
                                {report.sales_by_hour.map((entry) => (
                                    <div
                                        key={entry.hour}
                                        className="flex h-full flex-1 flex-col items-center justify-end"
                                        title={`${entry.hour}:00 — ${formatPrice(entry.total)} (${entry.count})`}
                                    >
                                        <div
                                            className="w-full rounded-t bg-primary/70"
                                            style={{ height: `${Math.max(entry.total > 0 ? 4 : 0, (entry.total / maxHour) * 100)}%` }}
                                        />
                                        <span className="mt-1 hidden text-[10px] tabular-nums opacity-60 sm:block">
                                            {entry.hour}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </PanelCard>
                    </div>

                    <div className="mt-6 grid gap-4 lg:grid-cols-2">
                        <PanelCard title="Ventas por método de pago" icon="fa-credit-card">
                            {report.sales_by_method.length === 0 ? (
                                <EmptyState title="Sin pagos en este periodo" icon="fa-credit-card" />
                            ) : (
                                <table className="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Método</th>
                                            <th className="text-right">Pagos</th>
                                            <th className="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {report.sales_by_method.map((entry) => (
                                            <tr key={entry.method}>
                                                <td>{entry.label}</td>
                                                <td className="text-right tabular-nums">{entry.count}</td>
                                                <td className="text-right tabular-nums">{formatPrice(entry.total)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </PanelCard>

                        <PanelCard title="Top 10 productos" icon="fa-ranking-star">
                            {report.top_products.length === 0 ? (
                                <EmptyState title="Sin productos vendidos" icon="fa-burger" />
                            ) : (
                                <table className="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th className="text-right">Cant.</th>
                                            <th className="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {report.top_products.map((entry) => (
                                            <tr key={entry.name}>
                                                <td>{entry.name}</td>
                                                <td className="text-right tabular-nums">{entry.quantity}</td>
                                                <td className="text-right tabular-nums">{formatPrice(entry.total)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </PanelCard>

                        <PanelCard title="Ventas por categoría" icon="fa-layer-group">
                            {report.sales_by_category.length === 0 ? (
                                <EmptyState title="Sin ventas por categoría" icon="fa-layer-group" />
                            ) : (
                                <table className="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Categoría</th>
                                            <th className="text-right">Cant.</th>
                                            <th className="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {report.sales_by_category.map((entry) => (
                                            <tr key={entry.name}>
                                                <td>{entry.name}</td>
                                                <td className="text-right tabular-nums">{entry.quantity}</td>
                                                <td className="text-right tabular-nums">{formatPrice(entry.total)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </PanelCard>

                        <PanelCard title="Top mozos" icon="fa-user-tie">
                            {report.top_waiters.length === 0 ? (
                                <EmptyState title="Sin mozos con ventas" icon="fa-user-tie" />
                            ) : (
                                <table className="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Mozo</th>
                                            <th className="text-right">Pedidos</th>
                                            <th className="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {report.top_waiters.map((entry) => (
                                            <tr key={entry.name}>
                                                <td>{entry.name}</td>
                                                <td className="text-right tabular-nums">{entry.orders}</td>
                                                <td className="text-right tabular-nums">{formatPrice(entry.total)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </PanelCard>
                    </div>
                </>
            ) : null}
        </AppLayout>
    );
}
