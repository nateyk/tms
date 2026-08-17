<?php

namespace Tests\Feature;

use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBaseline;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use App\Services\TyreUsageTrackingService;
use Database\Seeders\RealFleetAuditSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealFleetAuditSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_it_imports_the_complete_workbook_manifest_idempotently(): void
    {
        $this->seed(RealFleetAuditSeeder::class);
        $this->seed(RealFleetAuditSeeder::class);

        $this->assertSame(419, Tyre::query()->count());
        $this->assertSame(36, Vehicle::query()->count());
        $this->assertSame(18, VehicleCombination::query()->where('status', 'active')->count());
        $this->assertSame(419, TyreAssignment::query()->where('status', 'active')->count());
        $this->assertSame(152044, Vehicle::query()->where('plate_number', 'ET-3-A17807')->value('odometer'));
        $this->assertSame(171742, Vehicle::query()->where('plate_number', 'ET-3-A14761')->value('odometer'));
        $this->assertSame(178505, Vehicle::query()->where('plate_number', 'ET-3-A14763')->value('odometer'));
        $this->assertSame(164391, Vehicle::query()->where('plate_number', 'ET-3-A17806')->value('odometer'));
        $this->assertSame(155789, Vehicle::query()->where('plate_number', 'ET-3-A17749')->value('odometer'));
        $this->assertDatabaseHas('tyres', ['serial_number' => 'KE04157E204']);
        $this->assertDatabaseHas('tyres', ['serial_number' => 'KB07235E901']);
        $this->assertDatabaseHas('tyres', ['serial_number' => '26C0133323']);
        $this->assertDatabaseHas('tyres', ['serial_number' => 'J234C23099']);
        $this->assertDatabaseHas('tyre_baselines', ['baseline_odometer' => 152044, 'baseline_percentage' => 95]);

        $a27049 = Vehicle::query()->where('plate_number', 'ET-3-A27049')->firstOrFail();
        $this->assertFalse($a27049->activeTyreAssignments()->whereIn('position_code', ['A', 'B'])->exists());
    }

    public function test_reading_monitoring_merges_attached_trailer_positions_into_the_power_unit_map(): void
    {
        $this->seed(RealFleetAuditSeeder::class);

        $power = Vehicle::query()->where('plate_number', 'ET-3-A14763')->firstOrFail();
        $trailerTyre = Tyre::query()->where('serial_number', 'RE02283G13')->firstOrFail();
        $admin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('tyres.reading-monitoring.show', $power))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'tyres',
                fn ($tyres) => collect($tyres)->contains(
                    fn (array $tyre) => $tyre['id'] === $trailerTyre->id
                        && $tyre['current_position_code'] === 'K'
                )
            ));
    }

    public function test_a14761_import_keeps_baseline_and_latest_km_for_used_km_calculation(): void
    {
        $this->seed(RealFleetAuditSeeder::class);

        $power = Vehicle::query()->where('plate_number', 'ET-3-A14761')->firstOrFail();
        $trailer = Vehicle::query()->where('plate_number', 'ET-3-34051')->firstOrFail();
        $tyre = Tyre::query()->where('serial_number', '25C0874961')->firstOrFail();

        $this->assertSame(171742, $power->odometer);
        $this->assertSame(171742, $trailer->odometer);
        $this->assertDatabaseHas('vehicle_odometer_readings', [
            'vehicle_id' => $power->id,
            'odometer' => 171742,
        ]);
        $this->assertDatabaseMissing('vehicle_odometer_readings', [
            'vehicle_id' => $power->id,
            'odometer' => 184142,
        ]);
        $this->assertSame(171742, TyreBaseline::query()->where('tyre_id', $tyre->id)->value('baseline_odometer'));

        $usage = app(TyreUsageTrackingService::class)->calculateTyreUsage($tyre->fresh());

        $this->assertSame(0, $usage['total_used_km']);
        $this->assertSame(171742, $usage['current_vehicle_odometer']);
    }
}
