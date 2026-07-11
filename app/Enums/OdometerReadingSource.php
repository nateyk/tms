<?php

namespace App\Enums;

enum OdometerReadingSource: string
{
    case Manual = 'manual';
    case Movement = 'movement';
    case Baseline = 'baseline';
    case Import = 'import';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Movement => 'Movement',
            self::Baseline => 'Baseline',
            self::Import => 'Import',
        };
    }
}
