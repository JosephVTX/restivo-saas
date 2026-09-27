<?php

namespace App\Enums;

enum DocumentType: string
{
    case NotaVenta = 'nota_venta';
    case Boleta = 'boleta';
    case Factura = 'factura';
    case NotaCredito = 'nota_credito';

    public function label(): string
    {
        return match ($this) {
            self::NotaVenta => 'Nota de venta',
            self::Boleta => 'Boleta de venta',
            self::Factura => 'Factura',
            self::NotaCredito => 'Nota de crédito',
        };
    }

    /**
     * SUNAT document type code (catalogue 01). Notes of sale are not fiscal.
     */
    public function sunatCode(): ?string
    {
        return match ($this) {
            self::Factura => '01',
            self::Boleta => '03',
            self::NotaCredito => '07',
            self::NotaVenta => null,
        };
    }

    public function isElectronic(): bool
    {
        return $this->sunatCode() !== null;
    }

    /**
     * @return array<int, self>
     */
    public static function electronic(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type) => $type->isElectronic()));
    }
}
