<?php

namespace Tests\Unit;

use App\Services\VehicleTyreLayoutBuilder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VehicleTyreLayoutBuilderTest extends TestCase
{
    #[Test]
    public function it_builds_ten_tyre_power_layout_with_axles(): void
    {
        $builder = new VehicleTyreLayoutBuilder;
        $positions = $builder->buildLayout(10, 3, 'P')['positions'];

        $this->assertCount(10, $positions);
        $this->assertSame('P1', $positions[0]['code']);
        $this->assertSame(1, $positions[0]['axle']);
        $this->assertSame('single', $positions[0]['dual']);
        $this->assertSame('P10', $positions[9]['code']);
        $this->assertSame(3, $positions[9]['axle']);
    }

    #[Test]
    public function it_builds_twelve_tyre_trailer_layout(): void
    {
        $builder = new VehicleTyreLayoutBuilder;
        $positions = $builder->buildLayout(12, 3, 'T')['positions'];

        $this->assertCount(12, $positions);
        $this->assertSame('T1', $positions[0]['code']);
        $this->assertSame('left', $positions[0]['side']);
        $this->assertSame('outer', $positions[0]['dual']);
    }

    #[Test]
    public function it_upgrades_legacy_grid_layouts(): void
    {
        $builder = new VehicleTyreLayoutBuilder;
        $legacy = [
            'layout_version' => 1,
            'positions' => [
                ['code' => 'P1', 'label' => 'P1', 'x' => 100, 'y' => 80, 'axle' => 1, 'side' => 'left', 'dual' => 'single'],
            ],
        ];

        $positions = $builder->resolvePositions($legacy, 10, 3, 'P');

        $this->assertCount(10, $positions);
        $this->assertSame('single', $positions[0]['dual']);
        $this->assertSame('outer', $positions[2]['dual']);
    }

    #[Test]
    public function it_places_steer_singles_wide_and_dual_pairs_close(): void
    {
        $builder = new VehicleTyreLayoutBuilder;
        $positions = $builder->buildLayout(10, 3, 'P')['positions'];

        $steerL = $positions[0];
        $steerR = $positions[1];
        $driveOuterL = $positions[2];
        $driveInnerL = $positions[3];

        $this->assertLessThan($steerR['x'], $steerL['x']);
        $this->assertLessThan($driveInnerL['x'], $driveOuterL['x']);
        $this->assertLessThan(50, abs($driveInnerL['x'] - $driveOuterL['x']));
    }
}
