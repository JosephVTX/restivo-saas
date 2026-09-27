<?php

namespace App\Enums;

enum BillingMode: string
{
    case Beta = 'beta';
    case Production = 'production';

    public function label(): string
    {
        return match ($this) {
            self::Beta => 'Pruebas (beta)',
            self::Production => 'Producción',
        };
    }

    public function isProduction(): bool
    {
        return $this === self::Production;
    }
}
