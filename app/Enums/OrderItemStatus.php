<?php

namespace App\Enums;

enum OrderItemStatus: string
{
    case Pending = 'pending';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Preparing => 'Preparando',
            self::Ready => 'Listo',
            self::Delivered => 'Entregado',
            self::Void => 'Anulado',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::Preparing, self::Ready], true);
    }
}
