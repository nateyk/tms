<?php

namespace Tests\Feature;

use App\Models\Tyre;
use App\Models\TyreBaseline;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TyreUsageTrackingService;
use Database\Seeders\RealFleetAuditSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealFleetAuditSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_all_supplied_audits_without_replacing_existing_combinations(): void
    {
        $this->seed(RealFleetAuditSeeder::class);

        $this->assertSame(95, Tyre::query()->count());
        $this->assertSame(8, Vehicle::query()->count());
        $this->assertSame(184142, Vehicle::query()->where('plate_number', 'ET-3-A14761')->value('odometer'));
        $this->assertSame(178505, Vehicle::query()->where('plate_number', 'ET-3-A14763')->value('odometer'));
        $this->assertDatabaseHas('tyres', ['serial_number' => 'KE04157E204']);
        $this->assertDatabaseHas('tyres', ['serial_number' => 'KB07235E901']);
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

        $this->assertSame(184142, $power->odometer);
        $this->assertSame(184142, $trailer->odometer);
        $this->assertDatabaseHas('vehicle_odometer_readings', [
            'vehicle_id' => $power->id,
            'odometer' => 171742,
        ]);
        $this->assertDatabaseHas('vehicle_odometer_readings', [
            'vehicle_id' => $power->id,
            'odometer' => 184142,
        ]);
        $this->assertSame(171742, TyreBaseline::query()->where('tyre_id', $tyre->id)->value('baseline_odometer'));

        $usage = app(TyreUsageTrackingService::class)->calculateTyreUsage($tyre->fresh());

        $this->assertSame(12400, $usage['total_used_km']);
        $this->assertSame(184142, $usage['current_vehicle_odometer']);
    }
}
