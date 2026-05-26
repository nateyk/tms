<?php

namespace App\Enums;

enum TyreAssignmentStatus: string
{
    case Active = 'active';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Removed => 'Removed',
        };
    }
}
