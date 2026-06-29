<?php

namespace App\Enums;

enum TyreLocationType: string
{
    case Store = 'store';
    case PowerVehicle = 'power_vehicle';
    case Trailer = 'trailer';
    case MaintenanceCenter = 'maintenance_center';
    case DisposalYard = 'disposal_yard';

    public function label(): string
    {
        return match ($this) {
            self::Store => 'Store',
            self::PowerVehicle => 'Power Vehicle',
            self::Trailer => 'Trailer',
            self::MaintenanceCenter => 'Maintenance Center',
            self::DisposalYard => 'Disposal Yard',
        };
    }
}
