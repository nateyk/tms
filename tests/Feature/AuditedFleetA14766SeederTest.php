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
        $this->assertSame(18, Tyre::query()->count());

        $frontLeft = Tyre::query()->where('serial_number', 'RF05022U109')->firstOrFail();
        $this->assertSame($power->id, $frontLeft->current_location_id);
        $this->assertSame('A', $frontLeft->current_position_code);
        $this->assertSame(60.0, (float) $frontLeft->baseline->baseline_percentage);
        $this->assertSame(60.0, (float) $frontLeft->inspections()->firstOrFail()->audited_remaining_percentage);

        $trailerSpare = Tyre::query()->where('serial_number', 'KE04156I512')->firstOrFail();
        $this->assertSame($trailer->id, $trailerSpare->current_location_id);
        $this->assertSame('W', $trailerSpare->current_position_code);

        $powerSpare = Tyre::query()->where('serial_number', 'S104C25090')->firstOrFail();
        $this->assertSame($power->id, $powerSpare->current_location_id);
        $this->assertSame('X', $powerSpare->current_position_code);
        $this->assertDatabaseMissing('tyres', ['serial_number' => 'NO NUMBER']);
    }
}
