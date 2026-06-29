<?php

namespace App\Support;

class TyrePositionFormatter
{
    public static function display(?string $positionCode): string
    {
        if (! filled($positionCode)) {
            return '-';
        }

        $positionCode = strtoupper(trim($positionCode));

        if (str_starts_with($positionCode, 'SPARE-')) {
            return substr($positionCode, 6) ?: $positionCode;
        }

        if (preg_match('/^[PTR](\d+)$/', $positionCode, $matches)) {
            $index = (int) $matches[1];

            if ($index >= 1 && $index <= 26) {
                return chr(64 + $index);
            }
        }

        return $positionCode;
    }
}
