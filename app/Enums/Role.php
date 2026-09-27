<?php

namespace App\Enums;

/**
 * Default tenant roles. Owner is assigned to the user that creates a tenant.
 * Roles are stored per tenant through spatie's teams feature.
 */
enum Role: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Cashier = 'cashier';
    case Waiter = 'waiter';
    case Kitchen = 'kitchen';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Propietario',
            self::Admin => 'Administrador',
            self::Cashier => 'Cajero',
            self::Waiter => 'Mozo',
            self::Kitchen => 'Cocinero',
            self::Member => 'Miembro',
        };
    }

    /**
     * Roles that operate the point of sale and appear in staff pickers.
     *
     * @return array<int, self>
     */
    public static function staff(): array
    {
        return [self::Admin, self::Cashier, self::Waiter, self::Kitchen];
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}
