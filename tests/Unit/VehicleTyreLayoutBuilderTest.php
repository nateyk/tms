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
        $this->assertSame('A', $positions[0]['display_code']);
        $this->assertSame(1, $positions[0]['axle']);
        $this->assertSame('single', $positions[0]['dual']);
        $this->assertSame('P10', $positions[9]['code']);
        $this->assertSame('J', $positions[9]['display_code']);
        $this->assertSame(3, $positions[9]['axle']);
    }

    #[Test]
    public function it_builds_twelve_tyre_trailer_layout(): void
    {
        $builder = new VehicleTyreLayoutBuilder;
        $positions = $builder->buildLayout(12, 3, 'T')['positions'];

        $this->assertCount(12, $positions);
        $this->assertSame('T1', $positions[0]['code']);
        $this->assertSame('A', $positions[0]['display_code']);
        $this->assertSame('left', $positions[0]['side']);
        $this->assertSame('outer', $positions[0]['dual']);
        $this->assertSame('L', $positions[11]['display_code']);
    }

    #[Test]
    public function it_preserves_internal_position_codes_while_exposing_paper_labels(): void
    {
        $builder = new VehicleTyreLayoutBuilder;
        $positions = $builder->buildLayout(24, 6, 'P')['positions'];

        $this->assertSame('P1', $positions[0]['code']);
        $this->assertSame('A', $positions[0]['display_code']);
        $this->assertSame('P11', $positions[10]['code']);
        $this->assertSame('W', $positions[10]['display_code']);
        $this->assertSame('center', $positions[10]['side']);
        $this->assertSame('Spare wheel between 1st and 2nd group', $positions[10]['label']);
        $this->assertSame('P16', $positions[15]['code']);
        $this->assertSame('X', $positions[15]['display_code']);
        $this->assertSame('center', $positions[15]['side']);
        $this->assertSame('Spare wheel between tag and rear group', $positions[15]['label']);
        $this->assertSame('P24', $positions[23]['code']);
        $this->assertSame('V', $positions[23]['display_code']);
        $this->assertLessThan($positions[4]['y'], $positions[0]['y']);
        $this->assertLessThan($positions[20]['y'], $positions[15]['y']);
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
