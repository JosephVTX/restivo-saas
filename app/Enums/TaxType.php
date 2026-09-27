<?php

namespace App\Enums;

/**
 * SUNAT IGV affectation for a product line. The value is stored as a technical
 * identifier; `sunatCode()` maps it to the SUNAT catalogue 07 code used by
 * Greenter when building the electronic document.
 */
enum TaxType: string
{
    case Gravado = 'gravado';
    case Exonerado = 'exonerado';
    case Inafecto = 'inafecto';

    public function label(): string
    {
        return match ($this) {
            self::Gravado => 'Gravado (IGV 18%)',
            self::Exonerado => 'Exonerado',
            self::Inafecto => 'Inafecto',
        };
    }

    public function sunatCode(): string
    {
        return match ($this) {
            self::Gravado => '10',
            self::Exonerado => '20',
            self::Inafecto => '30',
        };
    }

    public function isTaxed(): bool
    {
        return $this === self::Gravado;
    }
}
