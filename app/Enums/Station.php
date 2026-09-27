<?php

namespace App\Enums;

enum Station: string
{
    case Kitchen = 'kitchen';
    case Bar = 'bar';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Kitchen => 'Cocina',
            self::Bar => 'Barra',
            self::None => 'Sin preparación',
        };
    }

    public function needsPreparation(): bool
    {
        return $this !== self::None;
    }
}
