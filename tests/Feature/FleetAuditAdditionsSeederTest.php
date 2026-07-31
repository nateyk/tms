<?php

namespace Tests\Feature;

use App\Models\Tyre;
use App\Models\Vehicle;
use Database\Seeders\FleetAuditAdditionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetAuditAdditionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_reimport_closes_positions_blank_in_the_audit_sheet(): void
    {
        $this->seed(FleetAuditAdditionsSeeder::class);

        $trailer = Vehicle::query()->where('plate_number', 'ET-3-36814')->firstOrFail();

        $this->assertFalse($trailer->activeTyreAssignments()->whereIn('position_code', ['Q', 'R'])->exists());
        $this->assertFalse(Tyre::query()
            ->where('current_location_type', 'trailer')
            ->where('current_location_id', $trailer->id)
            ->whereIn('current_position_code', ['Q', 'R'])
            ->exists());
        $this->assertSame(168446, $trailer->odometer);
    }
}
