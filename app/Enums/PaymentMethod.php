<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Yape = 'yape';
    case Plin = 'plin';
    case Card = 'card';
    case Transfer = 'transfer';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Yape => 'Yape',
            self::Plin => 'Plin',
            self::Card => 'Tarjeta',
            self::Transfer => 'Transferencia',
            self::Other => 'Otro',
        };
    }
}
