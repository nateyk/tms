<?php

namespace App\Support;

class TyrePositionHelper
{
    /**
     * Get spare tyre position codes.
     */
    public static function sparePositions(): array
    {
        return ['W', 'X'];
    }

    /**
     * Get running tyre position codes (A-V).
     */
    public static function runningPositions(): array
    {
        return range('A', 'V');
    }

    /**
     * Check if a position is a spare position.
     */
    public static function isSparePosition(?string $position): bool
    {
        return in_array(strtoupper((string) $position), self::sparePositions(), true);
    }

    /**
     * Check if a position is a running position.
     */
    public static function isRunningPosition(?string $position): bool
    {
        return in_array(strtoupper((string) $position), self::runningPositions(), true);
    }

    /**
     * Get label for spare position.
     */
    public static function spareLabel(string $position): string
    {
        return match (strtoupper($position)) {
            'W' => 'Power Spare',
            'X' => 'Trailer Spare',
            default => 'Spare',
        };
    }

    /**
     * Get position type (running or spare).
     */
    public static function getPositionType(?string $position): string
    {
        return self::isSparePosition($position) ? 'spare' : 'running';
    }
}
