<?php

namespace App\Services\Cash;

use App\Enums\CashMovementType;
use App\Enums\CashSessionStatus;
use App\Enums\PaymentMethod;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cash-register (turno de caja) lifecycle and arqueo math.
 *
 * A tenant has at most one open session at a time. Expected cash is derived
 * from the opening float + cash payments + manual in/out movements, never
 * stored ad-hoc, so the arqueo is always reproducible.
 */
class CashSessionService
{
    public function activeSession(): ?CashSession
    {
        return CashSession::query()->open()->latest('id')->first();
    }

    /**
     * @param  array{opening_amount?: float|int, notes?: string|null}  $attributes
     */
    public function open(User $user, array $attributes = []): CashSession
    {
        return DB::transaction(function () use ($user, $attributes): CashSession {
            if ($this->activeSession() !== null) {
                throw new RuntimeException('Ya existe un turno de caja abierto.');
            }

            return CashSession::query()->create([
                'user_id' => $user->id,
                'status' => CashSessionStatus::Open,
                'opening_amount' => $attributes['opening_amount'] ?? 0,
                'notes' => $attributes['notes'] ?? null,
                'opened_at' => now(),
            ])->refresh();
        });
    }

    /**
     * @param  array{closing_amount?: float|int, notes?: string|null}  $attributes
     */
    public function close(CashSession $session, array $attributes, ?User $closer = null): CashSession
    {
        return DB::transaction(function () use ($session, $attributes, $closer): CashSession {
            if (! $session->isOpen()) {
                throw new RuntimeException('El turno de caja ya está cerrado.');
            }

            $expected = $this->expectedCash($session);
            $closing = round((float) ($attributes['closing_amount'] ?? 0), 2);

            $session->status = CashSessionStatus::Closed;
            $session->expected_amount = $expected;
            $session->closing_amount = $closing;
            $session->difference = round($closing - $expected, 2);
            $session->closed_by_id = $closer?->id;

            if (array_key_exists('notes', $attributes)) {
                $session->notes = $attributes['notes'];
            }

            $session->closed_at = now();
            $session->save();

            return $session->refresh();
        });
    }

    /**
     * @param  array{type: string, amount: float|int, concept: string, notes?: string|null}  $attributes
     */
    public function registerMovement(CashSession $session, array $attributes, ?User $user = null): CashMovement
    {
        return DB::transaction(function () use ($session, $attributes, $user): CashMovement {
            if (! $session->isOpen()) {
                throw new RuntimeException('No se pueden registrar movimientos en un turno cerrado.');
            }

            return $session->movements()->create([
                'user_id' => $user?->id,
                'type' => $attributes['type'],
                'amount' => $attributes['amount'],
                'concept' => $attributes['concept'],
                'notes' => $attributes['notes'] ?? null,
            ])->refresh();
        });
    }

    public function expectedCash(CashSession $session): float
    {
        $cashPayments = (float) $session->payments()
            ->where('method', PaymentMethod::Cash->value)
            ->sum('amount');

        $in = (float) $session->movements()
            ->where('type', CashMovementType::In->value)
            ->sum('amount');

        $out = (float) $session->movements()
            ->where('type', CashMovementType::Out->value)
            ->sum('amount');

        return round((float) $session->opening_amount + $cashPayments + $in - $out, 2);
    }

    /**
     * @return array{opening_amount: float, cash_total: float, payments_total: float, tips_total: float, movements_in: float, movements_out: float, expected_cash: float, orders_count: int}
     */
    public function summary(CashSession $session): array
    {
        $payments = $session->payments()->get();

        return [
            'opening_amount' => round((float) $session->opening_amount, 2),
            'cash_total' => round((float) $payments->where('method', PaymentMethod::Cash)->sum('amount'), 2),
            'payments_total' => round((float) $payments->sum('amount'), 2),
            'tips_total' => round((float) $payments->sum('tip'), 2),
            'movements_in' => round((float) $session->movements()->where('type', CashMovementType::In->value)->sum('amount'), 2),
            'movements_out' => round((float) $session->movements()->where('type', CashMovementType::Out->value)->sum('amount'), 2),
            'expected_cash' => $this->expectedCash($session),
            'orders_count' => $payments->pluck('order_id')->unique()->count(),
        ];
    }
}
