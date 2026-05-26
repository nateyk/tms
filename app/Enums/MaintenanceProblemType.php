<?php

namespace App\Enums;

enum MaintenanceProblemType: string
{
    case Puncture = 'puncture';
    case AirLeakage = 'air_leakage';
    case SidewallDamage = 'sidewall_damage';
    case LowTread = 'low_tread';
    case AlignmentIssue = 'alignment_issue';
    case Retreading = 'retreading';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Puncture => 'Puncture',
            self::AirLeakage => 'Air Leakage',
            self::SidewallDamage => 'Sidewall Damage',
            self::LowTread => 'Low Tread',
            self::AlignmentIssue => 'Alignment Issue',
            self::Retreading => 'Retreading',
            self::Other => 'Other',
        };
    }
}
