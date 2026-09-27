import { Head } from '@inertiajs/react';
import { useState } from 'react';
import useSWR from 'swr';
import AppLayout from '@/components/layout/AppLayout';
import { EmptyState } from '@/components/ui/EmptyState';
import { Field } from '@/components/ui/Field';
import { Modal } from '@/components/ui/Modal';
import { PageHeader } from '@/components/ui/PageHeader';
import { PaymentModal } from '@/components/ui/PaymentModal';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { useCan } from '@/hooks/use-can';
import { useResource } from '@/hooks/use-resource';
import { api, fetcher, validationErrors } from '@/lib/http';
import { cashMovementTypeLabel } from '@/lib/labels';
import { toast } from '@/lib/toast';
import { cn, formatDate } from '@/lib/utils';
import { cashMovementSchema, closeCashSessionSchema, openCashSessionSchema } from '@/schemas/cash';
import type {
    CashMovement,
    CashMovementType,
    CashSession,
    CashSessionStatus,
    CashSummary,
    EnumOption,
    Order,
    PaymentMethod,
} from '@/types';

interface Props {
    paymentMethodOptions: EnumOption<PaymentMethod>[];
    cashMovementTypeOptions: EnumOption<CashMovementType>[];
    cashSessionStatusOptions: EnumOption<CashSessionStatus>[];
}

function formatPrice(value: string | number | null | undefined): string {
    return `S/ ${Number(value ?? 0).toFixed(2)}`;
}

function errorMessage(error: unknown): string | null {
    const message = (error as { response?: { data?: { message?: string } } })?.response?.data?.message;

    return message ?? null;
}

function errorsFor(error: unknown): Record<string, string> {
    const fieldErrors = validationErrors(error);
    const message = errorMessage(error);

    return message && Object.keys(fieldErrors).length === 0 ? { message } : fieldErrors;
}

function Kpi({ label, value, icon, accent }: { label: string; value: string; icon: string; accent?: string }) {
    return (
        <div className={cn('rounded-box border border-base-300 bg-base-100 p-4', accent)}>
            <p className="text-xs font-medium uppercase tracking-wide opacity-60">
                <i className={`fa-solid ${icon} mr-1`} aria-hidden="true" />
                {label}
            </p>
            <p className="mt-1 text-2xl font-bold tabular-nums">{value}</p>
        </div>
    );
}

export default function CashIndex({ paymentMethodOptions, cashMovementTypeOptions }: Props) {
    const can = useCan();

    const { data, isLoading, mutate: mutateCash } = useSWR<{ data: CashSession | null; summary: CashSummary | null }>(
        '/api/v1/cash/session',
        fetcher,
        { refreshInterval: 15000 },
    );

    const session = data?.data ?? null;
    const summary = data?.summary ?? null;

    const orders = useResource<Order>('/api/v1/orders', {
        per_page: 50,
        sort: '-created_at',
        filter: { active: 1 },
    });
    const pendingOrders = orders.items.filter((order) => Number(order.remaining) > 0.001);

    const [closedResult, setClosedResult] = useState<CashSession | null>(null);

    const [openAmount, setOpenAmount] = useState('0');
    const [openNotes, setOpenNotes] = useState('');
    const [openErrors, setOpenErrors] = useState<Record<string, string>>({});
    const [opening, setOpening] = useState(false);

    const [closeOpen, setCloseOpen] = useState(false);
    const [closeAmount, setCloseAmount] = useState('');
    const [closeNotes, setCloseNotes] = useState('');
    const [closeErrors, setCloseErrors] = useState<Record<string, string>>({});
    const [closing, setClosing] = useState(false);

    const [movementOpen, setMovementOpen] = useState(false);
    const [movementType, setMovementType] = useState<CashMovementType>('in');
    const [movementAmount, setMovementAmount] = useState('');
    const [movementConcept, setMovementConcept] = useState('');
    const [movementNotes, setMovementNotes] = useState('');
    const [movementErrors, setMovementErrors] = useState<Record<string, string>>({});
    const [savingMovement, setSavingMovement] = useState(false);

    const [payingOrder, setPayingOrder] = useState<Order | null>(null);

    const openSession = async () => {
        const parsed = openCashSessionSchema.safeParse({ opening_amount: openAmount, notes: openNotes });

        if (!parsed.success) {
            setOpenErrors(Object.fromEntries(parsed.error.issues.map((issue) => [String(issue.path[0]), issue.message])));

            return;
        }

        setOpening(true);
        setOpenErrors({});

        try {
            await api.post('/api/v1/cash/sessions', parsed.data);
            setOpenAmount('0');
            setOpenNotes('');
            setClosedResult(null);
            await mutateCash();
            toast.success('Caja abierta.');
        } catch (error) {
            setOpenErrors(errorsFor(error));
        } finally {
            setOpening(false);
        }
    };

    const closeSession = async () => {
        if (!session) {
            return;
        }

        const parsed = closeCashSessionSchema.safeParse({ closing_amount: closeAmount, notes: closeNotes });

        if (!parsed.success) {
            setCloseErrors(Object.fromEntries(parsed.error.issues.map((issue) => [String(issue.path[0]), issue.message])));

            return;
        }

        setClosing(true);
        setCloseErrors({});

        try {
            const response = await api.post<{ data: CashSession }>(
                `/api/v1/cash/sessions/${session.uuid}/close`,
                parsed.data,
            );

            setClosedResult(response.data);
            setCloseOpen(false);
            setCloseAmount('');
            setCloseNotes('');
            await mutateCash();
            toast.success('Caja cerrada.');
        } catch (error) {
            setCloseErrors(errorsFor(error));
        } finally {
            setClosing(false);
        }
    };

    const openMovement = (type: CashMovementType) => {
        setMovementType(type);
        setMovementAmount('');
        setMovementConcept('');
        setMovementNotes('');
        setMovementErrors({});
        setMovementOpen(true);
    };

    const submitMovement = async () => {
        if (!session) {
            return;
        }

        const parsed = cashMovementSchema.safeParse({
            type: movementType,
            amount: movementAmount,
            concept: movementConcept,
            notes: movementNotes,
        });

        if (!parsed.success) {
            setMovementErrors(
                Object.fromEntries(parsed.error.issues.map((issue) => [String(issue.path[0]), issue.message])),
            );

            return;
        }

        setSavingMovement(true);
        setMovementErrors({});

        try {
            await api.post(`/api/v1/cash/sessions/${session.uuid}/movements`, parsed.data);
            setMovementOpen(false);
            await mutateCash();
            toast.success('Movimiento registrado.');
        } catch (error) {
            setMovementErrors(errorsFor(error));
        } finally {
            setSavingMovement(false);
        }
    };

    const openPayment = (order: Order) => {
        setPayingOrder(order);
    };

    return (
        <AppLayout title="Caja">
            <Head title="Caja" />
            <PageHeader
                title="Caja"
                description="Apertura, cobros, movimientos y cierre del turno."
                actions={session ? <StatusBadge status={session.status} label={session.status_label} /> : undefined}
            />

            {isLoading ? (
                <div className="flex justify-center py-16">
                    <span className="loading loading-spinner" />
                </div>
            ) : !session ? (
                <div className="mt-6 grid gap-4 lg:grid-cols-2">
                    <div className="card border border-base-300 bg-base-100">
                        <div className="card-body gap-4">
                            <h2 className="card-title">
                                <i className="fa-solid fa-cash-register" aria-hidden="true" /> Abrir caja
                            </h2>
                            <p className="text-sm opacity-70">Registra el monto inicial del turno para empezar a cobrar.</p>

                            {openErrors.message ? (
                                <div className="alert alert-error">
                                    <i className="fa-solid fa-circle-exclamation" aria-hidden="true" />
                                    <span>{openErrors.message}</span>
                                </div>
                            ) : null}

                            <Field label="Monto de apertura (S/)" error={openErrors.opening_amount}>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.10"
                                    inputMode="decimal"
                                    className="input input-lg w-full text-2xl"
                                    value={openAmount}
                                    onChange={(event) => setOpenAmount(event.target.value)}
                                />
                            </Field>

                            <Field label="Nota (opcional)" error={openErrors.notes}>
                                <textarea
                                    className="textarea w-full"
                                    rows={2}
                                    value={openNotes}
                                    onChange={(event) => setOpenNotes(event.target.value)}
                                />
                            </Field>

                            <button
                                type="button"
                                className="btn btn-primary btn-lg h-16 text-lg"
                                disabled={opening || !can('cash.manage')}
                                onClick={openSession}
                            >
                                {opening ? (
                                    <span className="loading loading-spinner" />
                                ) : (
                                    <i className="fa-solid fa-lock-open" aria-hidden="true" />
                                )}
                                Abrir caja
                            </button>
                        </div>
                    </div>

                    {closedResult ? (
                        <div className="card border border-base-300 bg-base-100">
                            <div className="card-body gap-3">
                                <h2 className="card-title">
                                    <i className="fa-solid fa-lock" aria-hidden="true" /> Turno cerrado
                                </h2>
                                <div className="flex justify-between text-sm">
                                    <span className="opacity-70">Efectivo esperado</span>
                                    <span className="tabular-nums">{formatPrice(closedResult.expected_amount)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="opacity-70">Efectivo contado</span>
                                    <span className="tabular-nums">{formatPrice(closedResult.closing_amount)}</span>
                                </div>
                                <div
                                    className={cn(
                                        'flex justify-between rounded-box px-3 py-2 text-lg font-bold',
                                        Number(closedResult.difference) < 0 ? 'bg-error/15 text-error' : 'bg-success/15',
                                    )}
                                >
                                    <span>Diferencia</span>
                                    <span className="tabular-nums">{formatPrice(closedResult.difference)}</span>
                                </div>
                                <button
                                    type="button"
                                    className="btn btn-ghost btn-sm self-end"
                                    onClick={() => setClosedResult(null)}
                                >
                                    Cerrar aviso
                                </button>
                            </div>
                        </div>
                    ) : null}
                </div>
            ) : (
                <div className="mt-6 space-y-6">
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <Kpi label="Apertura" value={formatPrice(summary?.opening_amount)} icon="fa-door-open" />
                        <Kpi label="Ventas efectivo" value={formatPrice(summary?.cash_total)} icon="fa-money-bill-wave" />
                        <Kpi label="Total cobrado" value={formatPrice(summary?.payments_total)} icon="fa-hand-holding-dollar" />
                        <Kpi label="Propinas" value={formatPrice(summary?.tips_total)} icon="fa-hand-holding-heart" />
                        <Kpi label="Ingresos" value={formatPrice(summary?.movements_in)} icon="fa-arrow-down" accent="border-success/40" />
                        <Kpi label="Egresos" value={formatPrice(summary?.movements_out)} icon="fa-arrow-up" accent="border-error/40" />
                        <Kpi label="Efectivo esperado" value={formatPrice(summary?.expected_cash)} icon="fa-scale-balanced" accent="border-primary/40" />
                        <Kpi label="Pedidos cobrados" value={String(summary?.orders_count ?? 0)} icon="fa-receipt" />
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            className="btn btn-success h-12"
                            onClick={() => openMovement('in')}
                            disabled={!can('cash.manage')}
                        >
                            <i className="fa-solid fa-arrow-down" aria-hidden="true" /> Registrar ingreso
                        </button>
                        <button
                            type="button"
                            className="btn btn-error h-12"
                            onClick={() => openMovement('out')}
                            disabled={!can('cash.manage')}
                        >
                            <i className="fa-solid fa-arrow-up" aria-hidden="true" /> Registrar egreso
                        </button>
                        <button
                            type="button"
                            className="btn btn-outline h-12"
                            onClick={() => setCloseOpen(true)}
                            disabled={!can('cash.manage')}
                        >
                            <i className="fa-solid fa-lock" aria-hidden="true" /> Cerrar caja
                        </button>
                    </div>

                    <section>
                        <h2 className="mb-2 text-sm font-semibold uppercase tracking-wide opacity-60">
                            Movimientos de caja
                        </h2>
                        {(session.movements ?? []).length === 0 ? (
                            <div className="rounded-box border border-base-300 bg-base-100">
                                <EmptyState icon="fa-arrow-right-arrow-left" title="Sin movimientos">
                                    Registra ingresos o egresos de efectivo del turno.
                                </EmptyState>
                            </div>
                        ) : (
                            <div className="overflow-x-auto rounded-box border border-base-300 bg-base-100">
                                <table className="table">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Concepto</th>
                                            <th>Registrado por</th>
                                            <th>Fecha</th>
                                            <th className="text-right">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {(session.movements ?? []).map((movement: CashMovement) => (
                                            <tr key={movement.uuid} className="hover">
                                                <td>
                                                    <span
                                                        className={cn(
                                                            'badge badge-sm',
                                                            movement.type === 'in' ? 'badge-success' : 'badge-error',
                                                        )}
                                                    >
                                                        {movement.type_label || cashMovementTypeLabel(movement.type)}
                                                    </span>
                                                </td>
                                                <td className="text-sm">{movement.concept}</td>
                                                <td className="text-sm opacity-70">{movement.user ?? '—'}</td>
                                                <td className="text-sm opacity-70">{formatDate(movement.created_at)}</td>
                                                <td className="text-right font-medium tabular-nums">
                                                    {movement.type === 'out' ? '-' : '+'}
                                                    {formatPrice(movement.amount)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>
                </div>
            )}

            <section className="mt-6">
                <h2 className="mb-2 text-sm font-semibold uppercase tracking-wide opacity-60">Pedidos por cobrar</h2>
                {orders.isLoading ? (
                    <div className="flex justify-center py-10">
                        <span className="loading loading-spinner" />
                    </div>
                ) : pendingOrders.length === 0 ? (
                    <div className="rounded-box border border-base-300 bg-base-100">
                        <EmptyState icon="fa-receipt" title="No hay pedidos por cobrar">
                            Los pedidos abiertos aparecerán aquí para registrar su pago.
                        </EmptyState>
                    </div>
                ) : (
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {pendingOrders.map((order) => (
                            <article key={order.uuid} className="card border border-base-300 bg-base-100">
                                <div className="card-body gap-3 p-4">
                                    <div className="flex items-start justify-between gap-2">
                                        <div>
                                            <p className="text-lg font-bold">#{order.number}</p>
                                            <p className="text-xs opacity-70">
                                                {order.dining_table?.name ?? order.type_label}
                                                {order.waiter ? ` · ${order.waiter.name}` : ''}
                                            </p>
                                        </div>
                                        <StatusBadge status={order.status} label={order.status_label} />
                                    </div>

                                    <div className="space-y-1 text-sm">
                                        <div className="flex justify-between">
                                            <span className="opacity-70">Total</span>
                                            <span className="tabular-nums">{formatPrice(order.total)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="opacity-70">Pagado</span>
                                            <span className="tabular-nums">{formatPrice(order.paid_total)}</span>
                                        </div>
                                        <div className="flex justify-between text-base font-semibold">
                                            <span>Saldo</span>
                                            <span className="tabular-nums">{formatPrice(order.remaining)}</span>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        className="btn btn-primary h-14 text-lg"
                                        disabled={!can('payments.create')}
                                        onClick={() => openPayment(order)}
                                    >
                                        <i className="fa-solid fa-cash-register" aria-hidden="true" /> Cobrar
                                    </button>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
            </section>

            <Modal
                open={closeOpen}
                title="Cerrar caja"
                description="Ingresa el efectivo contado para calcular la diferencia."
                onClose={() => setCloseOpen(false)}
                footer={
                    <>
                        <button type="button" className="btn btn-ghost" onClick={() => setCloseOpen(false)}>
                            Cancelar
                        </button>
                        <button type="button" className="btn btn-primary" disabled={closing} onClick={closeSession}>
                            {closing ? <span className="loading loading-spinner" /> : null}
                            Cerrar caja
                        </button>
                    </>
                }
            >
                <div className="space-y-2">
                    {closeErrors.message ? (
                        <div className="alert alert-error">
                            <i className="fa-solid fa-circle-exclamation" aria-hidden="true" />
                            <span>{closeErrors.message}</span>
                        </div>
                    ) : null}
                    <p className="text-sm opacity-70">
                        Efectivo esperado: <strong className="tabular-nums">{formatPrice(summary?.expected_cash)}</strong>
                    </p>
                    <Field label="Efectivo contado (S/)" error={closeErrors.closing_amount}>
                        <input
                            type="number"
                            min="0"
                            step="0.10"
                            inputMode="decimal"
                            className="input input-lg w-full text-2xl"
                            value={closeAmount}
                            onChange={(event) => setCloseAmount(event.target.value)}
                        />
                    </Field>
                    <Field label="Nota (opcional)" error={closeErrors.notes}>
                        <textarea
                            className="textarea w-full"
                            rows={2}
                            value={closeNotes}
                            onChange={(event) => setCloseNotes(event.target.value)}
                        />
                    </Field>
                </div>
            </Modal>

            <Modal
                open={movementOpen}
                title={movementType === 'in' ? 'Registrar ingreso' : 'Registrar egreso'}
                description="Movimiento manual de efectivo en el turno."
                onClose={() => setMovementOpen(false)}
                footer={
                    <>
                        <button type="button" className="btn btn-ghost" onClick={() => setMovementOpen(false)}>
                            Cancelar
                        </button>
                        <button
                            type="button"
                            className={cn('btn', movementType === 'in' ? 'btn-success' : 'btn-error')}
                            disabled={savingMovement}
                            onClick={submitMovement}
                        >
                            {savingMovement ? <span className="loading loading-spinner" /> : null}
                            Registrar
                        </button>
                    </>
                }
            >
                <div className="space-y-2">
                    {movementErrors.message ? (
                        <div className="alert alert-error">
                            <i className="fa-solid fa-circle-exclamation" aria-hidden="true" />
                            <span>{movementErrors.message}</span>
                        </div>
                    ) : null}

                    {cashMovementTypeOptions.length > 0 ? (
                        <div className="join w-full">
                            {cashMovementTypeOptions.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    className={cn(
                                        'btn flex-1 join-item',
                                        movementType === option.value && 'btn-primary',
                                    )}
                                    onClick={() => setMovementType(option.value)}
                                >
                                    {cashMovementTypeLabel(option.value)}
                                </button>
                            ))}
                        </div>
                    ) : null}

                    <Field label="Monto (S/)" error={movementErrors.amount}>
                        <input
                            type="number"
                            min="0"
                            step="0.10"
                            inputMode="decimal"
                            className="input input-lg w-full text-2xl"
                            value={movementAmount}
                            onChange={(event) => setMovementAmount(event.target.value)}
                        />
                    </Field>
                    <Field label="Concepto" error={movementErrors.concept}>
                        <input
                            className="input w-full"
                            value={movementConcept}
                            onChange={(event) => setMovementConcept(event.target.value)}
                        />
                    </Field>
                    <Field label="Nota (opcional)" error={movementErrors.notes}>
                        <textarea
                            className="textarea w-full"
                            rows={2}
                            value={movementNotes}
                            onChange={(event) => setMovementNotes(event.target.value)}
                        />
                    </Field>
                </div>
            </Modal>

            <PaymentModal
                key={payingOrder?.uuid ?? 'closed'}
                order={payingOrder}
                paymentMethodOptions={paymentMethodOptions}
                onClose={() => setPayingOrder(null)}
                onPaid={() => {
                    void orders.mutate();
                    void mutateCash();
                }}
            />

        </AppLayout>
    );
}
