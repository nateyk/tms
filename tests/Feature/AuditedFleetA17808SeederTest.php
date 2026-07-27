<?php

namespace Tests\Feature;

use App\Models\Tyre;
use App\Models\Vehicle;
use Database\Seeders\AuditedFleetA17808Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditedFleetA17808SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_the_a17808_audit_as_an_attached_vehicle_combination(): void
    {
        $this->seed(AuditedFleetA17808Seeder::class);

        $power = Vehicle::query()->where('plate_number', 'ET-3-A17808')->firstOrFail();
        $trailer = Vehicle::query()->where('plate_number', 'ET-3-34425')->firstOrFail();

        $this->assertSame(146883, $power->odometer);
        $this->assertTrue($power->attachedTrailer()?->is($trailer) ?? false);
        $this->assertSame(24, Tyre::query()->count());

        $expectedFitment = [
            'A' => ['RF09013C403', 75], 'B' => ['RF08252T116', 75],
            'C' => ['KH02245C807', 75], 'D' => ['KH02255L302', 75],
            'E' => ['KH02257F611', 75], 'F' => ['KF08285F609', 75],
            'G' => ['KF08286S602', 75], 'H' => ['KH02247N712', 75],
            'I' => ['KF10237F607', 75], 'J' => ['KF09227I301', 75],
            'K' => ['RE02293Q613', 25], 'L' => ['RE02283Q603', 30],
            'M' => ['DP07123D301', 25], 'N' => ['KE06297R903', 40],
            'O' => ['KE07045J307', 40], 'P' => ['KE07045H503', 40],
            'Q' => ['KE07045C304', 40], 'R' => ['KE07045C301', 40],
            'S' => ['KE07045A403', 40], 'T' => ['KC06157J309', 40],
            'U' => ['KE07045R912', 40], 'V' => ['KE07045H812', 40],
            'W' => ['A170326', 0], 'X' => ['180248', 20],
        ];

        foreach ($expectedFitment as $position => [$serial, $percentage]) {
            $tyre = Tyre::query()->where('serial_number', $serial)->firstOrFail();
            $vehicle = $position <= 'J' ? $power : $trailer;

            $this->assertSame($vehicle->id, $tyre->current_location_id, $position.' must belong to the correct unit.');
            $this->assertSame($position, $tyre->current_position_code);
            $this->assertSame((float) $percentage, (float) $tyre->baseline->baseline_percentage);
        }

        $frontLeft = Tyre::query()->where('serial_number', 'RF09013C403')->firstOrFail();
        $this->assertSame($power->id, $frontLeft->current_location_id);
        $this->assertSame('A', $frontLeft->current_position_code);
        $this->assertSame(75.0, (float) $frontLeft->baseline->baseline_percentage);
        $this->assertSame(75.0, (float) $frontLeft->inspections()->firstOrFail()->audited_remaining_percentage);

        $passengerSide = Tyre::query()->where('serial_number', 'A170326')->firstOrFail();
        $this->assertSame($trailer->id, $passengerSide->current_location_id);
        $this->assertSame('W', $passengerSide->current_position_code);
        $this->assertSame(0.0, (float) $passengerSide->baseline->baseline_percentage);
        $this->assertStringContainsString('PASSANGER SIDE', $passengerSide->notes);

        $driverSide = Tyre::query()->where('serial_number', '180248')->firstOrFail();
        $this->assertSame($trailer->id, $driverSide->current_location_id);
        $this->assertSame('X', $driverSide->current_position_code);
        $this->assertStringContainsString('DRIVER SIDE', $driverSide->notes);
        $this->assertDatabaseMissing('tyre_assignments', ['asset_id' => $trailer->id, 'position_code' => 'Y']);
        $this->assertDatabaseMissing('tyre_assignments', ['asset_id' => $trailer->id, 'position_code' => 'Z']);
    }
}
