<?php

namespace Tests\Feature;

use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBaseline;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Models\VehicleOdometerReading;
use App\Services\TyreUsageTrackingService;
use App\Support\TyrePositionHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpareTyreKmLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_spare_position_helper_identifies_correct_positions(): void
    {
        // Test running positions
        $this->assertTrue(TyrePositionHelper::isRunningPosition('A'));
        $this->assertTrue(TyrePositionHelper::isRunningPosition('V'));
        $this->assertTrue(TyrePositionHelper::isRunningPosition('M'));
        
        // Test spare positions
        $this->assertTrue(TyrePositionHelper::isSparePosition('W'));
        $this->assertTrue(TyrePositionHelper::isSparePosition('X'));
        $this->assertFalse(TyrePositionHelper::isSparePosition('A'));
        $this->assertFalse(TyrePositionHelper::isSparePosition('V'));
        
        // Test case insensitivity
        $this->assertTrue(TyrePositionHelper::isSparePosition('w'));
        $this->assertTrue(TyrePositionHelper::isSparePosition('x'));
    }

    public function test_spare_position_helper_returns_correct_labels(): void
    {
        $this->assertEquals('Power Spare', TyrePositionHelper::spareLabel('W'));
        $this->assertEquals('Trailer Spare', TyrePositionHelper::spareLabel('X'));
        $this->assertEquals('Spare', TyrePositionHelper::spareLabel('Y'));
    }

    public function test_spare_position_helper_returns_correct_position_type(): void
    {
        $this->assertEquals('spare', TyrePositionHelper::getPositionType('W'));
        $this->assertEquals('spare', TyrePositionHelper::getPositionType('X'));
        $this->assertEquals('running', TyrePositionHelper::getPositionType('A'));
        $this->assertEquals('running', TyrePositionHelper::getPositionType('V'));
    }

    public function test_spare_tyre_does_not_accumulate_running_km(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $vehicleType = VehicleType::create([
            'name' => 'Test Vehicle Type',
            'asset_type' => 'power_vehicle',
            'axle_count' => 6,
            'tyre_count' => 24,
            'status' => 'active',
        ]);

        $vehicle = Vehicle::create([
            'vehicle_code' => 'TEST-001',
            'plate_number' => 'TEST-001',
            'asset_type' => 'power_vehicle',
            'vehicle_type_id' => $vehicleType->id,
            'odometer' => 100000,
            'status' => 'active',
        ]);

        // Create a spare tyre at position W
        $spareTyre = Tyre::create([
            'tyre_code' => 'SPARE-001',
            'serial_number' => 'SPARE001',
            'brand_id' => null,
            'size_id' => null,
            'current_location_id' => $vehicle->id,
            'current_location_type' => 'power_vehicle',
            'current_position_code' => 'W',
            'status' => 'active',
            'source' => 'purchased_new_tyre',
        ]);

        // Create baseline for spare tyre
        TyreBaseline::create([
            'tyre_id' => $spareTyre->id,
            'baseline_location_type' => 'power_vehicle',
            'baseline_location_id' => $vehicle->id,
            'baseline_position_code' => 'W',
            'baseline_odometer' => 90000,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->subDays(100),
            'created_by' => $user->id,
        ]);

        // Create active assignment for spare tyre
        TyreAssignment::create([
            'tyre_id' => $spareTyre->id,
            'asset_type' => 'power_vehicle',
            'asset_id' => $vehicle->id,
            'position_code' => 'W',
            'status' => 'active',
            'installed_odometer' => 90000,
            'installed_date' => now()->subDays(100),
        ]);

        // Update vehicle odometer
        $vehicle->odometer = 150000;
        $vehicle->save();

        VehicleOdometerReading::create([
            'vehicle_id' => $vehicle->id,
            'odometer' => 150000,
            'reading_date' => now(),
            'source' => 'manual',
            'recorded_by' => $user->id,
        ]);

        // Calculate usage
        $usageService = app(TyreUsageTrackingService::class);
        $usage = $usageService->calculateTyreUsage($spareTyre);

        // Spare tyre should have 0 active KM despite vehicle odometer increase
        $this->assertEquals(0, $usage['total_used_km']);
        $this->assertEquals(0, $usage['usage_percentage']);
        $this->assertEquals(100.0, $usage['estimated_remaining_percentage']);
    }

    public function test_running_tyre_accumulates_km_correctly(): void
    {
        $user = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);

        $vehicleType = VehicleType::create([
            'name' => 'Test Vehicle Type 2',
            'asset_type' => 'power_vehicle',
            'axle_count' => 6,
            'tyre_count' => 24,
            'status' => 'active',
        ]);

        $vehicle = Vehicle::create([
            'vehicle_code' => 'TEST-002',
            'plate_number' => 'TEST-002',
            'asset_type' => 'power_vehicle',
            'vehicle_type_id' => $vehicleType->id,
            'odometer' => 100000,
            'status' => 'active',
        ]);

        // Create a running tyre at position A
        $runningTyre = Tyre::create([
            'tyre_code' => 'RUNNING-001',
            'serial_number' => 'RUNNING001',
            'brand_id' => null,
            'size_id' => null,
            'current_location_id' => $vehicle->id,
            'current_location_type' => 'power_vehicle',
            'current_position_code' => 'A',
            'status' => 'active',
            'source' => 'purchased_new_tyre',
        ]);

        // Create baseline for running tyre
        TyreBaseline::create([
            'tyre_id' => $runningTyre->id,
            'baseline_location_type' => 'power_vehicle',
            'baseline_location_id' => $vehicle->id,
            'baseline_position_code' => 'A',
            'baseline_odometer' => 90000,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->subDays(100),
            'created_by' => $user->id,
        ]);

        // Create active assignment for running tyre
        TyreAssignment::create([
            'tyre_id' => $runningTyre->id,
            'asset_type' => 'power_vehicle',
            'asset_id' => $vehicle->id,
            'position_code' => 'A',
            'status' => 'active',
            'installed_odometer' => 90000,
            'installed_date' => now()->subDays(100),
        ]);

        // Update vehicle odometer
        $vehicle->odometer = 150000;
        $vehicle->save();

        VehicleOdometerReading::create([
            'vehicle_id' => $vehicle->id,
            'odometer' => 150000,
            'reading_date' => now(),
            'source' => 'manual',
            'recorded_by' => $user->id,
        ]);

        // Calculate usage
        $usageService = app(TyreUsageTrackingService::class);
        $usage = $usageService->calculateTyreUsage($runningTyre);

        // Running tyre should have accumulated KM
        $this->assertEquals(60000, $usage['total_used_km']); // 150000 - 90000
        $this->assertEquals(60.0, $usage['usage_percentage']); // 60000 / 100000
        $this->assertEquals(40.0, $usage['estimated_remaining_percentage']); // 100 - 60
    }

    public function test_spare_tyre_moved_to_running_position_starts_accumulating_km(): void
    {
        $user = User::create([
            'name' => 'Test User 3',
            'email' => 'test3@example.com',
            'password' => bcrypt('password'),
        ]);

        $vehicleType = VehicleType::create([
            'name' => 'Test Vehicle Type 3',
            'asset_type' => 'power_vehicle',
            'axle_count' => 6,
            'tyre_count' => 24,
            'status' => 'active',
        ]);

        $vehicle = Vehicle::create([
            'vehicle_code' => 'TEST-003',
            'plate_number' => 'TEST-003',
            'asset_type' => 'power_vehicle',
            'vehicle_type_id' => $vehicleType->id,
            'odometer' => 100000,
            'status' => 'active',
        ]);

        // Create a tyre initially at spare position W
        $tyre = Tyre::create([
            'tyre_code' => 'MOVED-001',
            'serial_number' => 'MOVED001',
            'brand_id' => null,
            'size_id' => null,
            'current_location_id' => $vehicle->id,
            'current_location_type' => 'power_vehicle',
            'current_position_code' => 'W',
            'status' => 'active',
            'source' => 'purchased_new_tyre',
        ]);

        // Create baseline
        TyreBaseline::create([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'power_vehicle',
            'baseline_location_id' => $vehicle->id,
            'baseline_position_code' => 'W',
            'baseline_odometer' => 90000,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->subDays(100),
            'created_by' => $user->id,
        ]);

        // Close old assignment at W
        $oldAssignment = TyreAssignment::create([
            'tyre_id' => $tyre->id,
            'asset_type' => 'power_vehicle',
            'asset_id' => $vehicle->id,
            'position_code' => 'W',
            'status' => 'removed',
            'installed_odometer' => 90000,
            'installed_date' => now()->subDays(100),
            'removed_odometer' => 100000,
            'removed_date' => now(),
            'km_used' => 0, // Spare tyres don't accumulate KM
        ]);

        // Move tyre to running position A
        $tyre->current_position_code = 'A';
        $tyre->save();

        TyreAssignment::create([
            'tyre_id' => $tyre->id,
            'asset_type' => 'power_vehicle',
            'asset_id' => $vehicle->id,
            'position_code' => 'A',
            'status' => 'active',
            'installed_odometer' => 100000,
            'installed_date' => now(),
        ]);

        // Update vehicle odometer
        $vehicle->odometer = 120000;
        $vehicle->save();

        VehicleOdometerReading::create([
            'vehicle_id' => $vehicle->id,
            'odometer' => 120000,
            'reading_date' => now(),
            'source' => 'manual',
            'recorded_by' => $user->id,
        ]);

        // Calculate usage
        $usageService = app(TyreUsageTrackingService::class);
        $usage = $usageService->calculateTyreUsage($tyre);

        // Tyre should only accumulate KM from when moved to running position
        $this->assertEquals(20000, $usage['total_used_km']); // 120000 - 100000 (active at A)
        $this->assertEquals(20.0, $usage['usage_percentage']);
        $this->assertEquals(80.0, $usage['estimated_remaining_percentage']);
    }
}
