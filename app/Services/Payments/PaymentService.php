<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Cash\CashSessionService;
use App\Services\Orders\OrderService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Records payments against an order (single or split) and closes the order
 * automatically once it is fully covered. Cash payments attach to the open
 * cash session so the arqueo stays accurate.
 */
class PaymentService
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly CashSessionService $cash,
    ) {}

    public function remaining(Order $order): float
    {
        $paid = (float) $order->payments()->sum('amount');

        return round((float) $order->total - $paid, 2);
    }

    /**
     * @param  array{method: string|PaymentMethod, amount: float|int, tip?: float|int, received_amount?: float|int|null, reference?: string|null, notes?: string|null}  $attributes
     */
    public function pay(Order $order, array $attributes, ?User $cashier = null): Payment
    {
        return DB::transaction(function () use ($order, $attributes, $cashier): Payment {
            if ($order->status->isOpen() === false) {
                throw new RuntimeException('El pedido ya fue pagado o cancelado.');
            }

            $method = $attributes['method'] instanceof PaymentMethod
                ? $attributes['method']
                : PaymentMethod::from($attributes['method']);

            $amount = round((float) $attributes['amount'], 2);

            if ($amount <= 0) {
                throw new RuntimeException('El monto del pago debe ser mayor a cero.');
            }

            $received = isset($attributes['received_amount'])
                ? round((float) $attributes['received_amount'], 2)
                : null;

            $change = ($method === PaymentMethod::Cash && $received !== null)
                ? round($received - $amount, 2)
                : null;

            $payment = $order->payments()->create([
                'cash_session_id' => $this->cash->activeSession()?->id,
                'user_id' => $cashier?->id,
                'method' => $method,
                'amount' => $amount,
                'tip' => round((float) ($attributes['tip'] ?? 0), 2),
                'received_amount' => $received,
                'change_amount' => $change,
                'reference' => $attributes['reference'] ?? null,
                'notes' => $attributes['notes'] ?? null,
                'paid_at' => now(),
            ]);

            $this->syncOrder($order);

            if ($this->remaining($order) <= 0.001 && $order->status->isOpen()) {
                $this->orders->close($order);
            }

            return $payment->refresh();
        });
    }

    private function syncOrder(Order $order): void
    {
        $order->paid_total = round((float) $order->payments()->sum('amount'), 2);
        $order->tip_total = round((float) $order->payments()->sum('tip'), 2);
        $order->save();
    }
}
