<?php

namespace App\Enums;

enum MovementType: string
{
    case StoreToVehicle = 'store_to_vehicle';
    case PowerToTrailer = 'power_to_trailer';
    case TrailerToPower = 'trailer_to_power';
    case PositionChangeSameAsset = 'position_change_same_asset';
    case VehicleToVehicle = 'vehicle_to_vehicle';
    case VehicleToStore = 'vehicle_to_store';
    case VehicleToMaintenance = 'vehicle_to_maintenance';
    case MaintenanceToVehicle = 'maintenance_to_vehicle';
    case MaintenanceToStore = 'maintenance_to_store';
    case StoreToMaintenance = 'store_to_maintenance';

    public function label(): string
    {
        return match ($this) {
            self::StoreToVehicle => 'Store to Vehicle',
            self::PowerToTrailer => 'Power to Trailer',
            self::TrailerToPower => 'Trailer to Power',
            self::PositionChangeSameAsset => 'Position Change (Same Asset)',
            self::VehicleToVehicle => 'Vehicle to Vehicle',
            self::VehicleToStore => 'Vehicle to Store',
            self::VehicleToMaintenance => 'Vehicle to Maintenance',
            self::MaintenanceToVehicle => 'Maintenance to Vehicle',
            self::MaintenanceToStore => 'Maintenance to Store',
            self::StoreToMaintenance => 'Store to Maintenance',
        };
    }
}
