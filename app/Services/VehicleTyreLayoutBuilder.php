<?php

namespace App\Services;

/**
 * Axle-grouped tyre layouts for the Konva top-down vehicle map.
 *
 * @see resources/js/tyre-map-konva.js
 */
class VehicleTyreLayoutBuilder
{
    public const LAYOUT_VERSION = 5;

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
            24 => $this->layoutTwentyFourTyres($prefix),
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
     * Paper form layout A-V with center spare tyres W and X.
     *
     * @return list<array<string, mixed>>
     */
    protected function layoutTwentyFourTyres(string $prefix): array
    {
        $template = [
            ['A', 1, 'left', 'single', 'Front Left'],
            ['B', 1, 'right', 'single', 'Front Right'],
            ['C', 2, 'left', 'outer', '1st Drive Axle Left Outer'],
            ['D', 2, 'left', 'inner', '1st Drive Axle Left Inner'],
            ['E', 2, 'right', 'inner', '1st Drive Axle Right Inner'],
            ['F', 2, 'right', 'outer', '1st Drive Axle Right Outer'],
            ['G', 3, 'left', 'outer', '2nd Drive Axle Left Outer'],
            ['H', 3, 'left', 'inner', '2nd Drive Axle Left Inner'],
            ['I', 3, 'right', 'inner', '2nd Drive Axle Right Inner'],
            ['J', 3, 'right', 'outer', '2nd Drive Axle Right Outer'],
            ['W', 4, 'center', 'single', 'Spare wheel between 1st and 2nd group'],
            ['K', 5, 'left', 'outer', 'Tag Axle Left Outer'],
            ['L', 5, 'left', 'inner', 'Tag Axle Left Inner'],
            ['M', 5, 'right', 'inner', 'Tag Axle Right Inner'],
            ['N', 5, 'right', 'outer', 'Tag Axle Right Outer'],
            ['X', 6, 'center', 'single', 'Spare wheel between tag and rear group'],
            ['O', 7, 'left', 'outer', 'Rear Axle Left Outer'],
            ['P', 7, 'left', 'inner', 'Rear Axle Left Inner'],
            ['Q', 7, 'right', 'inner', 'Rear Axle Right Inner'],
            ['R', 7, 'right', 'outer', 'Rear Axle Right Outer'],
            ['S', 8, 'left', 'outer', 'Rear Axle Left Outer (Rear)'],
            ['T', 8, 'left', 'inner', 'Rear Axle Left Inner (Rear)'],
            ['U', 8, 'right', 'inner', 'Rear Axle Right Inner (Rear)'],
            ['V', 8, 'right', 'outer', 'Rear Axle Right Outer (Rear)'],
        ];

        $positions = [];
        foreach ($template as $index => [$displayCode, $axle, $side, $dual, $label]) {
            $positions[] = $this->slot(
                sprintf('%s%d', $prefix, $index + 1),
                $label,
                $axle,
                $side,
                $dual,
                null,
                $displayCode
            );
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
                'single',
                $i
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
        ?int $displayIndex = null,
        ?string $displayCode = null,
    ): array {
        [$x, $y] = $this->coordinates($axle, $side, $dual);
        $legacyCode = $code;
        $displayCode ??= $this->paperDisplayCode($displayIndex ?? (int) preg_replace('/\D+/', '', $code));

        return [
            'code' => $displayCode,
            'display_code' => $displayCode,
            'legacy_code' => $legacyCode !== $displayCode ? $legacyCode : null,
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
        $y = 88 + (($axle - 1) * 86);

        if ($dual === 'single') {
            if ($side === 'center') {
                return [$centerX, $y];
            }

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

    protected function paperDisplayCode(int $index): string
    {
        if ($index >= 1 && $index <= 26) {
            return chr(64 + $index);
        }

        return (string) $index;
    }
}
