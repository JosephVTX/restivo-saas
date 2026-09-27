<?php

namespace App\Enums;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Predefined service periods the platform admin can grant a tenant. Trial
 * durations keep the tenant in the "Prueba" state; the rest are paid plans.
 */
enum PlanDuration: string
{
    case Days7 = '7_days';
    case Days14 = '14_days';
    case Days30 = '30_days';
    case Month1 = '1_month';
    case Months3 = '3_months';
    case Months6 = '6_months';
    case Months12 = '12_months';

    public function label(): string
    {
        return match ($this) {
            self::Days7 => '7 días de prueba',
            self::Days14 => '14 días de prueba',
            self::Days30 => '30 días de prueba',
            self::Month1 => '1 mes',
            self::Months3 => '3 meses',
            self::Months6 => '6 meses',
            self::Months12 => '12 meses',
        };
    }

    public function isTrial(): bool
    {
        return in_array($this, [self::Days7, self::Days14, self::Days30], true);
    }

    public function expiresAt(): CarbonInterface
    {
        $now = Carbon::now();

        return match ($this) {
            self::Days7 => $now->addDays(7),
            self::Days14 => $now->addDays(14),
            self::Days30 => $now->addDays(30),
            self::Month1 => $now->addMonthNoOverflow(),
            self::Months3 => $now->addMonthsNoOverflow(3),
            self::Months6 => $now->addMonthsNoOverflow(6),
            self::Months12 => $now->addMonthsNoOverflow(12),
        };
    }
}
