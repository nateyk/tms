<?php

namespace App\Enums;

enum TyreSource: string
{
    case PurchasedNewTyre = 'purchased_new_tyre';
    case ExistingVehicle = 'existing_vehicle';
    case NewPurchasedVehicle = 'new_purchased_vehicle';

    public function label(): string
    {
        return match ($this) {
            self::PurchasedNewTyre => 'Purchased New Tyre',
            self::ExistingVehicle => 'Existing Vehicle',
            self::NewPurchasedVehicle => 'New Purchased Vehicle',
        };
    }
}
