import { useState } from 'react';
import { Field } from '@/components/ui/Field';
import { Modal } from '@/components/ui/Modal';
import { api, validationErrors } from '@/lib/http';
import { paymentMethodLabel } from '@/lib/labels';
import { toast } from '@/lib/toast';
import { cn } from '@/lib/utils';
import { paymentSchema } from '@/schemas/payment';
import type { EnumOption, Order, PaymentMethod } from '@/types';

const methodIcons: Record<PaymentMethod, string> = {
    cash: 'fa-money-bill-wave',
    yape: 'fa-mobile-screen-button',
    plin: 'fa-mobile-screen-button',
    card: 'fa-credit-card',
    transfer: 'fa-building-columns',
    other: 'fa-ellipsis',
};

function formatPrice(value: string | number | null | undefined): string {
    return `S/ ${Number(value ?? 0).toFixed(2)}`;
}

function errorMessage(error: unknown): string | null {
    return (error as { response?: { data?: { message?: string } } })?.response?.data?.message ?? null;
}

function errorsFor(error: unknown): Record<string, string> {
    const fieldErrors = validationErrors(error);
    const message = errorMessage(error);

    return message && Object.keys(fieldErrors).length === 0 ? { message } : fieldErrors;
}

interface Props {
    /** Order to charge; `null` keeps the modal closed. */
    order: Order | null;
    paymentMethodOptions: EnumOption<PaymentMethod>[];
    onClose: () => void;
    /** Called after every successful payment so the caller can refresh its lists. */
    onPaid?: (order: Order) => void;
}

/**
 * Registers a (possibly split) payment for an order. Shared by the cashier
 * (Caja) and the waiter collecting at the table (POS).
 */
export function PaymentModal({ order, paymentMethodOptions, onClose, onPaid }: Props) {
    const [target, setTarget] = useState<Order | null>(order);
    const [method, setMethod] = useState<PaymentMethod>('cash');
    const [amount, setAmount] = useState(() => (order ? Number(order.remaining).toFixed(2) : ''));
    const [tip, setTip] = useState('');
    const [received, setReceived] = useState('');
    const [reference, setReference] = useState('');
    const [notes, setNotes] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);

    const change = method === 'cash' && received !== '' ? Number(received) - Number(amount || 0) : null;

    const resetFor = (value: Order) => {
        setMethod('cash');
        setAmount(Number(value.remaining).toFixed(2));
        setTip('');
        setReceived('');
        setReference('');
        setNotes('');
        setErrors({});
    };

    const submit = async () => {
        if (!target) {
            return;
        }

        const parsed = paymentSchema.safeParse({
            method,
            amount,
            tip: tip === '' ? undefined : tip,
            received_amount: method === 'cash' && received !== '' ? received : undefined,
            reference,
            notes,
        });

        if (!parsed.success) {
            setErrors(Object.fromEntries(parsed.error.issues.map((issue) => [String(issue.path[0]), issue.message])));

            return;
        }

        setSaving(true);
        setErrors({});

        try {
            await api.post(`/api/v1/orders/${target.uuid}/payments`, parsed.data);

            const fresh = await api.get<{ data: Order }>(`/api/v1/orders/${target.uuid}`);
            const updated = fresh.data;

            toast.success(`Pago de #${updated.number} registrado.`);
            onPaid?.(updated);

            if (Number(updated.remaining) > 0.001) {
                setTarget(updated);
                resetFor(updated);
            } else {
                onClose();
            }
        } catch (error) {
            setErrors(errorsFor(error));
        } finally {
            setSaving(false);
        }
    };

    return (
        <Modal
            open={order !== null}
            title={target ? `Cobrar pedido #${target.number}` : 'Cobrar'}
            description="Registra un pago total o dividido."
            onClose={onClose}
            footer={
                <>
                    <button type="button" className="btn btn-ghost" onClick={onClose}>
                        Cancelar
                    </button>
                    <button type="button" className="btn btn-primary" disabled={saving} onClick={submit}>
                        {saving ? <span className="loading loading-spinner" /> : null}
                        Cobrar {formatPrice(amount)}
                    </button>
                </>
            }
        >
            <div className="space-y-3">
                {errors.message ? (
                    <div className="alert alert-error">
                        <i className="fa-solid fa-circle-exclamation" aria-hidden="true" />
                        <span>{errors.message}</span>
                    </div>
                ) : null}

                {target ? (
                    <div className="rounded-box bg-base-200 px-3 py-2 text-sm">
                        <div className="flex justify-between">
                            <span className="opacity-70">Saldo pendiente</span>
                            <span className="font-semibold tabular-nums">{formatPrice(target.remaining)}</span>
                        </div>
                    </div>
                ) : null}

                <div>
                    <p className="mb-1 text-sm font-medium">Método de pago</p>
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        {paymentMethodOptions.map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                className={cn('btn h-16 flex-col gap-1 text-sm', method === option.value && 'btn-primary')}
                                onClick={() => setMethod(option.value)}
                            >
                                <i className={`fa-solid ${methodIcons[option.value]}`} aria-hidden="true" />
                                {paymentMethodLabel(option.value) || option.label}
                            </button>
                        ))}
                    </div>
                </div>

                <Field label="Monto (S/)" error={errors.amount}>
                    <input
                        type="number"
                        min="0"
                        step="0.10"
                        inputMode="decimal"
                        className="input input-lg w-full text-2xl"
                        value={amount}
                        onChange={(event) => setAmount(event.target.value)}
                    />
                </Field>

                <Field label="Propina (S/)" error={errors.tip}>
                    <input
                        type="number"
                        min="0"
                        step="0.10"
                        inputMode="decimal"
                        className="input w-full"
                        value={tip}
                        onChange={(event) => setTip(event.target.value)}
                    />
                </Field>

                {method === 'cash' ? (
                    <Field label="Monto recibido (S/)" error={errors.received_amount}>
                        <input
                            type="number"
                            min="0"
                            step="0.10"
                            inputMode="decimal"
                            className="input input-lg w-full text-2xl"
                            value={received}
                            onChange={(event) => setReceived(event.target.value)}
                        />
                        {change !== null ? (
                            <p
                                className={cn(
                                    'mt-1 text-lg font-semibold tabular-nums',
                                    change < 0 ? 'text-error' : 'text-success',
                                )}
                            >
                                Vuelto: {formatPrice(change)}
                            </p>
                        ) : null}
                    </Field>
                ) : null}

                <Field label="Referencia (opcional)" error={errors.reference}>
                    <input className="input w-full" value={reference} onChange={(event) => setReference(event.target.value)} />
                </Field>

                <Field label="Nota (opcional)" error={errors.notes}>
                    <textarea
                        className="textarea w-full"
                        rows={2}
                        value={notes}
                        onChange={(event) => setNotes(event.target.value)}
                    />
                </Field>
            </div>
        </Modal>
    );
}
