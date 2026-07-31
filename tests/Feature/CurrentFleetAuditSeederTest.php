<?php

namespace Tests\Feature;

use App\Models\Tyre;
use App\Models\TyreInspection;
use App\Models\Vehicle;
use Database\Seeders\CurrentFleetAuditSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentFleetAuditSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_audit_import_reconciles_both_combinations_idempotently(): void
    {
        $this->seed(CurrentFleetAuditSeeder::class);
        $this->seed(CurrentFleetAuditSeeder::class);

        $power = Vehicle::query()->where('plate_number', 'ET-3-A00765')->firstOrFail();
        $trailer = Vehicle::query()->where('plate_number', 'ET-3-34969')->firstOrFail();
        $emptyPositionTrailer = Vehicle::query()->where('plate_number', 'ET-3-36814')->firstOrFail();

        $this->assertSame(254529, $power->odometer);
        $this->assertSame(254529, $trailer->odometer);
        $this->assertSame(1, $power->activeCombinationAsPower()->where('trailer_vehicle_id', $trailer->id)->count());
        $this->assertSame(23, $power->activeTyreAssignments()->count() + $trailer->activeTyreAssignments()->count());
        $this->assertFalse($emptyPositionTrailer->activeTyreAssignments()->whereIn('position_code', ['Q', 'R'])->exists());
        $this->assertFalse(Tyre::query()
            ->where('current_location_type', 'trailer')
            ->where('current_location_id', $emptyPositionTrailer->id)
            ->whereIn('current_position_code', ['Q', 'R'])
            ->exists());

        $wTyre = Tyre::query()->where('serial_number', 'J234C23099')->firstOrFail();
        $this->assertSame($trailer->id, $wTyre->current_location_id);
        $this->assertSame('W', $wTyre->current_position_code);
        $this->assertSame(0.0, (float) TyreInspection::query()
            ->where('tyre_id', $wTyre->id)
            ->where('audit_odometer', 254529)
            ->value('audited_remaining_percentage'));

        $this->assertSame(1, Tyre::query()->where('serial_number', 'KE07117J305')->count());
        $this->assertSame(23, TyreInspection::query()->where('audit_odometer', 254529)->count());
        $this->assertSame(22, TyreInspection::query()->where('audit_odometer', 170248)->count());
    }
}
