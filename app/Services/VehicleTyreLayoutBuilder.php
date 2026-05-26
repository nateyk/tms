<?php

namespace App\Services;

/**
 * Axle-grouped tyre layouts for the Konva top-down vehicle map.
 *
 * @see resources/js/tyre-map-konva.js
 */
class VehicleTyreLayoutBuilder
{
    public const LAYOUT_VERSION = 3;

    public const STAGE_WIDTH = 880;

    public const STAGE_HEIGHT = 600;

    /**
     * @return array{layout_version: int, positions: list<array<string, mixed>>}
     */
    public function buildLayout(int $tyreCount, int $axleCount, string $prefix = 'P'): array
    {
        $positions = match ($tyreCount) {
            6 => $this->layoutSixTyres($prefix),
            10 => $this->layoutTenTyres($prefix),
            12 => $this->layoutTwelveTyres($prefix),
            default => $this->layoutGridFallback($tyreCount, $prefix),
        };

        return [
            'layout_version' => self::LAYOUT_VERSION,
            'positions' => $positions,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resolvePositions(?array $layoutJson, int $tyreCount, int $axleCount, string $prefix = 'P'): array
    {
        $stored = $layoutJson['positions'] ?? [];
        $version = (int) ($layoutJson['layout_version'] ?? 1);

        if ($version >= self::LAYOUT_VERSION && $this->positionsAreAxleReady($stored)) {
            return $this->normalizeCount($stored, $tyreCount, $axleCount, $prefix);
        }

        return $this->buildLayout($tyreCount, $axleCount, $prefix)['positions'];
    }

    /**
     * @param  list<array<string, mixed>>  $positions
     * @return list<array<string, mixed>>
     */
    protected function normalizeCount(array $positions, int $tyreCount, int $axleCount, string $prefix): array
    {
        if (count($positions) >= $tyreCount) {
            return array_slice($positions, 0, $tyreCount);
        }

        return $this->buildLayout($tyreCount, $axleCount, $prefix)['positions'];
    }

    /**
     * @param  list<array<string, mixed>>  $positions
     */
    protected function positionsAreAxleReady(array $positions): bool
    {
        if ($positions === []) {
            return false;
        }

        foreach ($positions as $position) {
            if (! isset($position['axle'], $position['side'], $position['dual'], $position['x'], $position['y'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function layoutSixTyres(string $prefix): array
    {
        return [
            $this->slot("{$prefix}1", 'Steer L', 1, 'left', 'single'),
            $this->slot("{$prefix}2", 'Steer R', 1, 'right', 'single'),
            $this->slot("{$prefix}3", 'Drive L Outer', 2, 'left', 'outer'),
            $this->slot("{$prefix}4", 'Drive L Inner', 2, 'left', 'inner'),
            $this->slot("{$prefix}5", 'Drive R Inner', 2, 'right', 'inner'),
            $this->slot("{$prefix}6", 'Drive R Outer', 2, 'right', 'outer'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function layoutTenTyres(string $prefix): array
    {
        return [
            $this->slot("{$prefix}1", 'Steer L', 1, 'left', 'single'),
            $this->slot("{$prefix}2", 'Steer R', 1, 'right', 'single'),
            $this->slot("{$prefix}3", 'Axle 2 L Outer', 2, 'left', 'outer'),
            $this->slot("{$prefix}4", 'Axle 2 L Inner', 2, 'left', 'inner'),
            $this->slot("{$prefix}5", 'Axle 2 R Inner', 2, 'right', 'inner'),
            $this->slot("{$prefix}6", 'Axle 2 R Outer', 2, 'right', 'outer'),
            $this->slot("{$prefix}7", 'Axle 3 L Outer', 3, 'left', 'outer'),
            $this->slot("{$prefix}8", 'Axle 3 L Inner', 3, 'left', 'inner'),
            $this->slot("{$prefix}9", 'Axle 3 R Inner', 3, 'right', 'inner'),
            $this->slot("{$prefix}10", 'Axle 3 R Outer', 3, 'right', 'outer'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function layoutTwelveTyres(string $prefix): array
    {
        $positions = [];
        $index = 1;

        for ($axle = 1; $axle <= 3; $axle++) {
            foreach (
                [
                    ['left', 'outer'],
                    ['left', 'inner'],
                    ['right', 'inner'],
                    ['right', 'outer'],
                ] as [$side, $dual]
            ) {
                $positions[] = $this->slot(
                    sprintf('%s%d', $prefix, $index),
                    sprintf('Axle %d %s %s', $axle, ucfirst($side), ucfirst($dual)),
                    $axle,
                    $side,
                    $dual
                );
                $index++;
            }
        }

        return $positions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function layoutGridFallback(int $tyreCount, string $prefix): array
    {
        $positions = [];

        for ($i = 1; $i <= $tyreCount; $i++) {
            $axle = (int) ceil($i / 4);
            $positions[] = $this->slot(
                "{$prefix}{$i}",
                "Position {$prefix}{$i}",
                max(1, $axle),
                $i % 2 === 1 ? 'left' : 'right',
                'single'
            );
        }

        return $positions;
    }

    /**
     * @return array<string, mixed>
     */
    protected function slot(
        string $code,
        string $label,
        int $axle,
        string $side,
        string $dual,
    ): array {
        [$x, $y] = $this->coordinates($axle, $side, $dual);

        return [
            'code' => $code,
            'label' => $label,
            'axle' => $axle,
            'side' => $side,
            'dual' => $dual,
            'x' => $x,
            'y' => $y,
        ];
    }

    /**
     * Top-down map: front axle at top, dual pairs side-by-side on each flank.
     *
     * @return array{0: int, 1: int}
     */
    protected function coordinates(int $axle, string $side, string $dual): array
    {
        $centerX = (int) (self::STAGE_WIDTH / 2);
        $y = match ($axle) {
            1 => 108,
            2 => 268,
            3 => 428,
            default => 108 + (($axle - 1) * 160),
        };

        if ($dual === 'single') {
            return [$side === 'left' ? 96 : self::STAGE_WIDTH - 96, $y];
        }

        $innerCenter = 112;
        $pairGap = 36;

        if ($side === 'left') {
            $x = $dual === 'outer'
                ? $centerX - $innerCenter - $pairGap
                : $centerX - $innerCenter;
        } else {
            $x = $dual === 'inner'
                ? $centerX + $innerCenter
                : $centerX + $innerCenter + $pairGap;
        }

        return [$x, $y];
    }
}
