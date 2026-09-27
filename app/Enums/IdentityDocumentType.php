<?php

namespace App\Enums;

/**
 * SUNAT identity document type (catalogue 06). The value is a technical
 * identifier; `sunatCode()` maps it to the code used when building electronic
 * documents.
 */
enum IdentityDocumentType: string
{
    case Ruc = 'ruc';
    case Dni = 'dni';
    case Ce = 'ce';
    case Passport = 'passport';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Ruc => 'RUC',
            self::Dni => 'DNI',
            self::Ce => 'Carnet de extranjería',
            self::Passport => 'Pasaporte',
            self::Other => 'Otro documento',
        };
    }

    public function sunatCode(): string
    {
        return match ($this) {
            self::Dni => '1',
            self::Ce => '4',
            self::Ruc => '6',
            self::Passport => '7',
            self::Other => '0',
        };
    }

    public function isRuc(): bool
    {
        return $this === self::Ruc;
    }
}
