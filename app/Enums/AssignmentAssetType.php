<?php

namespace App\Enums;

enum AssignmentAssetType: string
{
    case PowerVehicle = 'power_vehicle';
    case Trailer = 'trailer';

    public function label(): string
    {
        return match ($this) {
            self::PowerVehicle => 'Power Vehicle',
            self::Trailer => 'Trailer',
        };
    }
}
