<?php

namespace App\Enums;

enum TableStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Billing = 'billing';
    case Reserved = 'reserved';
    case Cleaning = 'cleaning';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Libre',
            self::Occupied => 'Ocupada',
            self::Billing => 'Por cobrar',
            self::Reserved => 'Reservada',
            self::Cleaning => 'Limpieza',
        };
    }

    public function isBusy(): bool
    {
        return in_array($this, [self::Occupied, self::Billing], true);
    }
}
