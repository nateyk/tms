<?php

namespace App\Enums;

enum TyreStatus: string
{
    case PendingApproval = 'pending_approval';
    case Available = 'available';
    case Active = 'active';
    case Maintenance = 'maintenance';
    case Damaged = 'damaged';
    case Disposed = 'disposed';

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Pending Approval',
            self::Available => 'Available',
            self::Active => 'Active',
            self::Maintenance => 'Maintenance',
            self::Damaged => 'Damaged',
            self::Disposed => 'Disposed',
        };
    }

    public function mapColor(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Available => 'blue',
            self::Maintenance => 'orange',
            self::Damaged => 'red',
            self::Disposed => 'black',
            self::PendingApproval => 'yellow',
        };
    }
}
