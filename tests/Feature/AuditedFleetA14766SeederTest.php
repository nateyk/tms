<?php

namespace Tests\Feature;

use App\Models\Tyre;
use App\Models\Vehicle;
use Database\Seeders\AuditedFleetA14766Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditedFleetA14766SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_the_a14766_audit_as_an_attached_vehicle_combination(): void
    {
        $this->seed(AuditedFleetA14766Seeder::class);

        $power = Vehicle::query()->where('plate_number', 'ET-3-A14766')->firstOrFail();
        $trailer = Vehicle::query()->where('plate_number', 'ET-3-34055')->firstOrFail();

        $this->assertSame(170416, $power->odometer);
        $this->assertTrue($power->attachedTrailer()?->is($trailer) ?? false);
        $this->assertSame(23, Tyre::query()->count());

        $expectedFitment = [
            'A' => ['RF05022U109', 60], 'B' => ['RF05122T175', 60],
            'C' => ['KF03256KS01', 90], 'D' => ['KF03225F503', 90],
            'E' => ['KF03257F508', 90], 'F' => ['KF03225N704', 90],
            'G' => ['KF09195J501', 90], 'H' => ['KF03236J705', 90],
            'I' => ['KF03227M511', 90], 'J' => ['KF03226L203', 90],
            'K' => ['KE10296E210', 40], 'L' => ['KD07157P909', 40],
            'M' => ['UNIDENTIFIED-ET-3-34055-M', 25], 'N' => ['KE04156R707', 25],
            'O' => ['KC06157M406', 20], 'P' => ['KB04065P704', 20],
            'Q' => ['BP07013I115', 25], 'R' => ['RD12162O609', 25],
            'S' => ['E651838', 25], 'T' => ['KD07157E605', 25],
            'U' => ['E563248', 20], 'V' => ['KC06195J302', 20],
            'W' => ['KC06027D305', 20],
        ];

        foreach ($expectedFitment as $position => [$serial, $percentage]) {
            $tyre = Tyre::query()->where('serial_number', $serial)->firstOrFail();
            $vehicle = $position <= 'J' ? $power : $trailer;

            $this->assertSame($vehicle->id, $tyre->current_location_id, $position.' must belong to the correct unit.');
            $this->assertSame($position, $tyre->current_position_code);
            $this->assertSame((float) $percentage, (float) $tyre->baseline->baseline_percentage);
        }

        $frontLeft = Tyre::query()->where('serial_number', 'RF05022U109')->firstOrFail();
        $this->assertSame($power->id, $frontLeft->current_location_id);
        $this->assertSame('A', $frontLeft->current_position_code);
        $this->assertSame(60.0, (float) $frontLeft->baseline->baseline_percentage);
        $this->assertSame(60.0, (float) $frontLeft->inspections()->firstOrFail()->audited_remaining_percentage);

        $trailerSpare = Tyre::query()->where('serial_number', 'KC06027D305')->firstOrFail();
        $this->assertSame($trailer->id, $trailerSpare->current_location_id);
        $this->assertSame('W', $trailerSpare->current_position_code);

        $unidentifiedTyre = Tyre::query()->where('serial_number', 'UNIDENTIFIED-ET-3-34055-M')->firstOrFail();
        $this->assertSame($trailer->id, $unidentifiedTyre->current_location_id);
        $this->assertSame('M', $unidentifiedTyre->current_position_code);
        $this->assertStringContainsString('NO NU MBER', $unidentifiedTyre->notes);
        $this->assertDatabaseMissing('tyres', ['serial_number' => 'NO NUMBER']);
        $this->assertDatabaseMissing('tyre_assignments', ['asset_id' => $power->id, 'position_code' => 'X']);
    }
}
