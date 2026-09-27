<?php

namespace App\Enums;

enum OrderType: string
{
    case DineIn = 'dine_in';
    case Takeaway = 'takeaway';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::DineIn => 'En mesa',
            self::Takeaway => 'Para llevar',
            self::Delivery => 'Delivery',
        };
    }
}
