<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Open = 'open';
    case Sent = 'sent';
    case Served = 'served';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierto',
            self::Sent => 'Enviado',
            self::Served => 'Servido',
            self::Paid => 'Pagado',
            self::Cancelled => 'Anulado',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Open, self::Sent, self::Served], true);
    }
}
