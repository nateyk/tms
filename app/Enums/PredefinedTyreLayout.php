<?php

namespace App\Enums;

enum PredefinedTyreLayout: string
{
    case RigidTruck6 = 'rigid_6';
    case PowerUnit10 = 'power_10';
    case HeavyTruck24 = 'heavy_truck_24';
    case Trailer12 = 'trailer_12';

    public function label(): string
    {
        return match ($this) {
            self::RigidTruck6 => 'Rigid truck - 6 tyres (steer + 1 dual axle)',
            self::PowerUnit10 => 'Power unit - 10 tyres (steer + 2 dual axles)',
            self::HeavyTruck24 => 'Heavy truck - 24 tyres (6 axles + W/X spares)',
            self::Trailer12 => 'Trailer - 12 tyres (3 dual axles)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::RigidTruck6 => '2 steer singles + 4 drive duals',
            self::PowerUnit10 => '2 steer singles + 8 drive duals (standard truck)',
            self::HeavyTruck24 => 'A-V position guide with 22 mounted tyre positions plus spare wheels W and X',
            self::Trailer12 => '12 positions, all dual pairs',
        };
    }

    public function tyreCount(): int
    {
        return match ($this) {
            self::RigidTruck6 => 6,
            self::PowerUnit10 => 10,
            self::HeavyTruck24 => 24,
            self::Trailer12 => 12,
        };
    }

    public function axleCount(): int
    {
        return match ($this) {
            self::RigidTruck6 => 2,
            self::PowerUnit10 => 3,
            self::HeavyTruck24 => 6,
            self::Trailer12 => 3,
        };
    }

    public function spareCount(): int
    {
        return match ($this) {
            self::HeavyTruck24 => 2,
            default => 0,
        };
    }

    public function positionPrefix(): string
    {
        return match ($this) {
            self::RigidTruck6 => 'R',
            self::PowerUnit10, self::HeavyTruck24 => 'P',
            self::Trailer12 => 'T',
        };
    }

    public function suggestedAssetType(): AssetType
    {
        return match ($this) {
            self::RigidTruck6 => AssetType::RigidTruck,
            self::PowerUnit10, self::HeavyTruck24 => AssetType::PowerVehicle,
            self::Trailer12 => AssetType::Trailer,
        };
    }

    public static function tryFromTyreCount(int $tyreCount): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->tyreCount() === $tyreCount) {
                return $case;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
