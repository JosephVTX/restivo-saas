<?php

namespace App\Enums;

enum TenantStatus: string
{
    case Active = 'active';
    case Trial = 'trial';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Trial => 'Prueba',
            self::Suspended => 'Suspendido',
            self::Cancelled => 'Cancelado',
        };
    }

    public function isUsable(): bool
    {
        return in_array($this, [self::Active, self::Trial], true);
    }
}
