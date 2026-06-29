<?php

namespace App\Enums;

enum CombinationStatus: string
{
    case Active = 'active';
    case Detached = 'detached';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Detached => 'Detached',
            self::Cancelled => 'Cancelled',
        };
    }
}
