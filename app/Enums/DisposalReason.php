<?php

namespace App\Enums;

enum DisposalReason: string
{
    case FullDamage = 'full_damage';
    case WornOut = 'worn_out';
    case Sold = 'sold';
    case Lost = 'lost';
    case Scrap = 'scrap';

    public function label(): string
    {
        return match ($this) {
            self::FullDamage => 'Full Damage',
            self::WornOut => 'Worn Out',
            self::Sold => 'Sold',
            self::Lost => 'Lost',
            self::Scrap => 'Scrap',
        };
    }
}
