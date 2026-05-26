<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Maintenance = 'maintenance';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Maintenance => 'Maintenance',
            self::Retired => 'Retired',
        };
    }
}
