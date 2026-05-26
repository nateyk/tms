<?php

namespace App\Enums;

enum AssetType: string
{
    case PowerVehicle = 'power_vehicle';
    case Trailer = 'trailer';
    case RigidTruck = 'rigid_truck';
    case Pickup = 'pickup';
    case Bus = 'bus';

    public function label(): string
    {
        return match ($this) {
            self::PowerVehicle => 'Power Vehicle',
            self::Trailer => 'Trailer',
            self::RigidTruck => 'Rigid Truck',
            self::Pickup => 'Pickup',
            self::Bus => 'Bus',
        };
    }

    public function isTyreMountable(): bool
    {
        return in_array($this, [self::PowerVehicle, self::Trailer, self::RigidTruck, self::Pickup, self::Bus], true);
    }
}
