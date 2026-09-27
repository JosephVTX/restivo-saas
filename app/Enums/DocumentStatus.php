<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Annulled = 'annulled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Issued => 'Emitido',
            self::Accepted => 'Aceptado',
            self::Rejected => 'Rechazado',
            self::Annulled => 'Anulado',
        };
    }
}
