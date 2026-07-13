<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBaseline;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleOdometerReading;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TyreUsageTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    private static int $tyreSequence = 10000;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
    }

    public function test_tyre_without_baseline_returns_baseline_required()
    {
        $tyre = $this->createAvailableTyre();

        $usage = app(\App\Services\TyreUsageTrackingService::class)->calculateTyreUsage($tyre);

        $this->assertFalse($usage['has_baseline']);
        $this->assertEquals('Baseline Required', $usage['status']);
        $this->assertNull($usage['total_used_km']);
        $this->assertNull($usage['usage_percentage']);
        $this->assertNull($usage['estimated_remaining_percentage']);
    }

    public function test_tyre_with_baseline_but_no_assignments_returns_baseline_percentage_remaining()
    {
        $tyre = $this->createAvailableTyre();

        $baseline = TyreBaseline::query()->create([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => Store::query()->first()->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $usage = app(\App\Services\TyreUsageTrackingService::class)->calculateTyreUsage($tyre);

        $this->assertTrue($usage['has_baseline']);
        $this->assertEquals(100.00, $usage['baseline_percentage']);
        $this->assertEquals(100000, $usage['expected_life_km']);
        $this->assertEquals(0, $usage['total_used_km']);
        $this->assertEquals(0.0, $usage['usage_percentage']);
        $this->assertEquals(100.00, $usage['estimated_remaining_percentage']);
        $this->assertEquals('Good', $usage['status']);
    }

    public function test_closed_assignment_km_is_included()
    {
        $tyre = $this->createAvailableTyre();
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();

        $baseline = TyreBaseline::query()->create([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => Store::query()->first()->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->subDays(10)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        TyreAssignment::query()->create([
            'tyre_id' => $tyre->id,
            'asset_type' => 'power_vehicle',
            'asset_id' => $vehicle->id,
            'position_code' => 'FL',
            'installed_odometer' => 1000,
            'removed_odometer' => 5000,
            'km_used' => 4000,
            'installed_date' => now()->subDays(5)->toDateString(),
            'removed_date' => now()->subDays(1)->toDateString(),
            'status' => 'removed',
            'movement_id' => null,
        ]);

        $usage = app(\App\Services\TyreUsageTrackingService::class)->calculateTyreUsage($tyre);

        $this->assertEquals(4000, $usage['total_used_km']);
        $this->assertEquals(4.0, $usage['usage_percentage']);
        $this->assertEquals(96.00, $usage['estimated_remaining_percentage']);
    }

    public function test_active_assignment_km_is_calculated_from_latest_vehicle_odometer()
    {
        $tyre = $this->createAvailableTyre();
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();

        $baseline = TyreBaseline::query()->create([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => Store::query()->first()->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->subDays(10)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // Create active assignment
        TyreAssignment::query()->create([
            'tyre_id' => $tyre->id,
            'asset_type' => 'power_vehicle',
            'asset_id' => $vehicle->id,
            'position_code' => 'FL',
            'installed_odometer' => 1000,
            'installed_date' => now()->subDays(5)->toDateString(),
            'status' => 'active',
            'movement_id' => null,
        ]);

        // Update tyre location to match active assignment
        $tyre->update([
            'current_location_type' => 'power_vehicle',
            'current_location_id' => $vehicle->id,
            'current_position_code' => 'FL',
            'status' => 'active',
        ]);

        // Create odometer reading
        VehicleOdometerReading::query()->create([
            'vehicle_id' => $vehicle->id,
            'odometer' => 3000,
            'reading_date' => now()->toDateString(),
            'source' => 'manual',
            'recorded_by' => $this->user->id,
        ]);

        $usage = app(\App\Services\TyreUsageTrackingService::class)->calculateTyreUsage($tyre);

        $this->assertEquals(2000, $usage['total_used_km']); // 3000 - 1000
        $this->assertEquals(2.0, $usage['usage_percentage']);
        $this->assertEquals(98.00, $usage['estimated_remaining_percentage']);
    }

    public function test_active_assignment_uses_vehicle_baseline_odometer_when_assignment_started_at_zero()
    {
        $tyre = $this->createAvailableTyre();
        $vehicleType = VehicleType::query()->create([
            'name' => 'Usage Baseline Test Truck',
            'asset_type' => 'power_vehicle',
            'axle_count' => 2,
            'tyre_count' => 6,
            'status' => 'active',
        ]);
        $vehicle = Vehicle::query()->create([
            'vehicle_code' => 'USAGE-BASELINE-TRUCK',
            'plate_number' => 'USAGE-BASELINE-TRUCK',
            'asset_type' => 'power_vehicle',
            'vehicle_type_id' => $vehicleType->id,
            'odometer' => 146945,
            'status' => 'active',
        ]);

        TyreBaseline::query()->create([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'power_vehicle',
            'baseline_location_id' => $vehicle->id,
            'baseline_position_code' => 'B',
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 80000,
            'baseline_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        TyreAssignment::query()->create([
            'tyre_id' => $tyre->id,
            'asset_type' => 'power_vehicle',
            'asset_id' => $vehicle->id,
            'position_code' => 'B',
            'installed_odometer' => 0,
            'installed_date' => now()->toDateString(),
            'status' => 'active',
            'movement_id' => null,
        ]);

        $tyre->update([
            'current_location_type' => 'power_vehicle',
            'current_location_id' => $vehicle->id,
            'current_position_code' => 'B',
            'status' => 'active',
        ]);

        VehicleOdometerReading::query()->create([
            'vehicle_id' => $vehicle->id,
            'odometer' => 146783,
            'reading_date' => now()->toDateString(),
            'source' => 'baseline',
            'recorded_by' => $this->user->id,
        ]);

        VehicleOdometerReading::query()->create([
            'vehicle_id' => $vehicle->id,
            'odometer' => 146945,
            'reading_date' => now()->toDateString(),
            'source' => 'manual',
            'recorded_by' => $this->user->id,
        ]);

        $usageService = app(\App\Services\TyreUsageTrackingService::class);
        $this->assertEquals(146945, $usageService->getLatestVehicleOdometer($vehicle));
        $this->assertEquals(146783, $usageService->getVehicleBaselineOdometer($vehicle));
        $this->assertEquals(162, $usageService->calculateActiveAssignmentKm($tyre));

        $usage = $usageService->calculateTyreUsage($tyre);

        $this->assertEquals(162, $usage['total_used_km']);
        $this->assertEqualsWithDelta(0.2025, $usage['usage_percentage'], 0.0001);
        $this->assertEquals(99.8, $usage['estimated_remaining_percentage']);
    }

    public function test_vehicle_odometer_readings_latest_value_is_preferred_over_vehicles_odometer()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();

        // Set vehicle odometer to 5000
        $vehicle->update(['odometer' => 5000]);

        // Create a higher reading in vehicle_odometer_readings
        VehicleOdometerReading::query()->create([
            'vehicle_id' => $vehicle->id,
            'odometer' => 7000,
            'reading_date' => now()->toDateString(),
            'source' => 'manual',
            'recorded_by' => $this->user->id,
        ]);

        $latestOdometer = app(\App\Services\TyreUsageTrackingService::class)->getLatestVehicleOdometer($vehicle);

        $this->assertEquals(7000, $latestOdometer);
    }

    public function test_vehicles_odometer_is_used_as_fallback()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();

        // Set vehicle odometer
        $vehicle->update(['odometer' => 5000]);

        // No readings in vehicle_odometer_readings
        $latestOdometer = app(\App\Services\TyreUsageTrackingService::class)->getLatestVehicleOdometer($vehicle);

        $this->assertEquals(5000, $latestOdometer);
    }

    public function test_tyre_in_store_does_not_accumulate_active_km()
    {
        $tyre = $this->createAvailableTyre();
        $store = Store::query()->first();

        $baseline = TyreBaseline::query()->create([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => $store->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->subDays(10)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // Ensure tyre is in store
        $tyre->update([
            'current_location_type' => 'store',
            'current_location_id' => $store->id,
            'current_position_code' => null,
            'status' => 'available',
        ]);

        $usage = app(\App\Services\TyreUsageTrackingService::class)->calculateTyreUsage($tyre);

        $this->assertEquals(0, $usage['total_used_km']);
    }

    public function test_disposed_tyre_does_not_accumulate_active_km()
    {
        $tyre = $this->createAvailableTyre();
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();

        $baseline = TyreBaseline::query()->create([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => Store::query()->first()->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->subDays(10)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // Create active assignment
        TyreAssignment::query()->create([
            'tyre_id' => $tyre->id,
            'asset_type' => 'power_vehicle',
            'asset_id' => $vehicle->id,
            'position_code' => 'FL',
            'installed_odometer' => 1000,
            'installed_date' => now()->subDays(5)->toDateString(),
            'status' => 'active',
            'movement_id' => null,
        ]);

        // Mark tyre as disposed
        $tyre->update(['status' => 'disposed']);

        $usage = app(\App\Services\TyreUsageTrackingService::class)->calculateTyreUsage($tyre);

        $this->assertEquals(0, $usage['total_used_km']);
    }

    public function test_estimated_remaining_percentage_cannot_go_below_0()
    {
        $tyre = $this->createAvailableTyre();
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();

        $baseline = TyreBaseline::query()->create([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => Store::query()->first()->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 50.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->subDays(10)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // Create closed assignment that exceeds expected life
        TyreAssignment::query()->create([
            'tyre_id' => $tyre->id,
            'asset_type' => 'power_vehicle',
            'asset_id' => $vehicle->id,
            'position_code' => 'FL',
            'installed_odometer' => 0,
            'removed_odometer' => 150000,
            'km_used' => 150000,
            'installed_date' => now()->subDays(5)->toDateString(),
            'removed_date' => now()->subDays(1)->toDateString(),
            'status' => 'removed',
            'movement_id' => null,
        ]);

        $usage = app(\App\Services\TyreUsageTrackingService::class)->calculateTyreUsage($tyre);

        $this->assertEquals(0.0, $usage['estimated_remaining_percentage']);
        $this->assertEquals('Finished', $usage['status']);
    }

    public function test_estimated_remaining_percentage_cannot_go_above_baseline_percentage()
    {
        $tyre = $this->createAvailableTyre();

        $baseline = TyreBaseline::query()->create([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => Store::query()->first()->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 80.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->subDays(10)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $usage = app(\App\Services\TyreUsageTrackingService::class)->calculateTyreUsage($tyre);

        $this->assertEquals(80.00, $usage['estimated_remaining_percentage']);
        $this->assertEquals(80.00, $usage['baseline_percentage']);
    }

    public function test_usage_continues_across_multiple_assignments()
    {
        $tyre = $this->createAvailableTyre();
        $vehicle1 = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $vehicle2 = Vehicle::query()->where('asset_type', 'power_vehicle')
            ->where('id', '!=', $vehicle1->id)
            ->firstOrFail();

        $baseline = TyreBaseline::query()->create([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => Store::query()->first()->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->subDays(20)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        // First assignment (closed)
        TyreAssignment::query()->create([
            'tyre_id' => $tyre->id,
            'asset_type' => 'power_vehicle',
            'asset_id' => $vehicle1->id,
            'position_code' => 'FL',
            'installed_odometer' => 1000,
            'removed_odometer' => 5000,
            'km_used' => 4000,
            'installed_date' => now()->subDays(15)->toDateString(),
            'removed_date' => now()->subDays(10)->toDateString(),
            'status' => 'removed',
            'movement_id' => null,
        ]);

        // Second assignment (closed)
        TyreAssignment::query()->create([
            'tyre_id' => $tyre->id,
            'asset_type' => 'power_vehicle',
            'asset_id' => $vehicle2->id,
            'position_code' => 'FR',
            'installed_odometer' => 2000,
            'removed_odometer' => 8000,
            'km_used' => 6000,
            'installed_date' => now()->subDays(8)->toDateString(),
            'removed_date' => now()->subDays(3)->toDateString(),
            'status' => 'removed',
            'movement_id' => null,
        ]);

        $usage = app(\App\Services\TyreUsageTrackingService::class)->calculateTyreUsage($tyre);

        $this->assertEquals(10000, $usage['total_used_km']); // 4000 + 6000
        $this->assertEquals(10.0, $usage['usage_percentage']);
        $this->assertEquals(90.00, $usage['estimated_remaining_percentage']);
    }

    public function test_status_labels_are_correct()
    {
        $this->assertEquals('Baseline Required', app(\App\Services\TyreUsageTrackingService::class)->getUsageStatus(null, false));
        $this->assertEquals('Good', app(\App\Services\TyreUsageTrackingService::class)->getUsageStatus(80.0, true));
        $this->assertEquals('Good', app(\App\Services\TyreUsageTrackingService::class)->getUsageStatus(60.0, true));
        $this->assertEquals('Watch', app(\App\Services\TyreUsageTrackingService::class)->getUsageStatus(59.99, true));
        $this->assertEquals('Watch', app(\App\Services\TyreUsageTrackingService::class)->getUsageStatus(30.0, true));
        $this->assertEquals('Low', app(\App\Services\TyreUsageTrackingService::class)->getUsageStatus(29.99, true));
        $this->assertEquals('Low', app(\App\Services\TyreUsageTrackingService::class)->getUsageStatus(10.0, true));
        $this->assertEquals('End of Life', app(\App\Services\TyreUsageTrackingService::class)->getUsageStatus(9.99, true));
        $this->assertEquals('End of Life', app(\App\Services\TyreUsageTrackingService::class)->getUsageStatus(0.01, true));
        $this->assertEquals('Finished', app(\App\Services\TyreUsageTrackingService::class)->getUsageStatus(0.0, true));
    }

    public function test_usage_history_returns_correct_data()
    {
        $tyre = $this->createAvailableTyre();
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();

        TyreAssignment::query()->create([
            'tyre_id' => $tyre->id,
            'asset_type' => 'power_vehicle',
            'asset_id' => $vehicle->id,
            'position_code' => 'FL',
            'installed_odometer' => 1000,
            'removed_odometer' => 5000,
            'km_used' => 4000,
            'installed_date' => now()->subDays(10)->toDateString(),
            'removed_date' => now()->subDays(5)->toDateString(),
            'status' => 'removed',
            'movement_id' => null,
        ]);

        $history = app(\App\Services\TyreUsageTrackingService::class)->getUsageHistory($tyre);

        $this->assertCount(1, $history);
        $this->assertEquals('FL', $history->first()['position_code']);
        $this->assertEquals(1000, $history->first()['installed_odometer']);
        $this->assertEquals(5000, $history->first()['removed_odometer']);
        $this->assertEquals(4000, $history->first()['km_used']);
        $this->assertFalse($history->first()['is_active']);
    }

    private function createAvailableTyre(): Tyre
    {
        $store = Store::query()->firstOrFail();
        $sequence = ++self::$tyreSequence;

        return Tyre::query()->create([
            'tyre_code' => "USAGE-TYR-{$sequence}",
            'serial_number' => "USAGE-SN-{$sequence}",
            'current_location_type' => 'store',
            'current_location_id' => $store->id,
            'current_position_code' => null,
            'status' => 'available',
            'source' => 'purchased_new_tyre',
        ]);
    }
}
