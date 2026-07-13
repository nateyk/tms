<?php

namespace Tests\Feature;

use App\Exceptions\TyreBusinessException;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleOdometerReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleOdometerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
    }

    public function test_update_odometer()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $initialOdometer = $vehicle->odometer;

        $reading = app(\App\Services\VehicleOdometerService::class)->updateOdometer(
            $vehicle,
            $initialOdometer + 1000,
            'manual',
            null,
            $this->user->id,
        );

        $this->assertInstanceOf(VehicleOdometerReading::class, $reading);
        $this->assertEquals($initialOdometer + 1000, $reading->odometer);
        $this->assertEquals('manual', $reading->source->value);
        $this->assertEquals($this->user->id, $reading->recorded_by);

        $vehicle->refresh();
        $this->assertEquals($initialOdometer + 1000, $vehicle->odometer);
        $this->assertNotNull($vehicle->odometer_last_updated_at);
        $this->assertEquals($this->user->id, $vehicle->odometer_last_updated_by);
    }

    public function test_cannot_set_odometer_lower_than_current()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $currentOdometer = $vehicle->odometer;
        $lowerOdometer = $currentOdometer - 100;

        $this->expectException(TyreBusinessException::class);
        $this->expectExceptionMessage("Odometer reading ({$lowerOdometer}) cannot be lower than the latest recorded odometer ({$currentOdometer}).");

        app(\App\Services\VehicleOdometerService::class)->updateOdometer(
            $vehicle,
            $lowerOdometer,
            'manual',
            null,
            $this->user->id,
        );
    }

    public function test_odometer_last_updated_tracking()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();

        $this->assertNull($vehicle->odometer_last_updated_at);
        $this->assertNull($vehicle->odometer_last_updated_by);

        app(\App\Services\VehicleOdometerService::class)->updateOdometer(
            $vehicle,
            $vehicle->odometer + 1000,
            'manual',
            null,
            $this->user->id,
        );

        $vehicle->refresh();
        $this->assertNotNull($vehicle->odometer_last_updated_at);
        $this->assertEquals($this->user->id, $vehicle->odometer_last_updated_by);
    }

    public function test_record_movement_odometer()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $initialOdometer = $vehicle->odometer;

        $reading = app(\App\Services\VehicleOdometerService::class)->recordMovementOdometer(
            $vehicle,
            $initialOdometer + 500,
            123, // movement_id
            $this->user->id,
        );

        $this->assertInstanceOf(VehicleOdometerReading::class, $reading);
        $this->assertEquals('movement', $reading->source->value);
        $this->assertEquals(123, $reading->source_id);
        $this->assertEquals($initialOdometer + 500, $reading->odometer);
    }

    public function test_record_baseline_odometer()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $initialOdometer = $vehicle->odometer;

        $reading = app(\App\Services\VehicleOdometerService::class)->updateOdometer(
            $vehicle,
            $initialOdometer + 200,
            'baseline',
            null,
            $this->user->id,
        );

        $this->assertEquals('baseline', $reading->source->value);
    }

    public function test_vehicle_baseline_odometer_can_be_saved_from_odometer_page()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $baselineOdometer = $vehicle->odometer + 250;

        $response = $this->actingAs($this->user)
            ->put(route('fleet.vehicles.odometer.update', $vehicle), [
                'odometer' => $baselineOdometer,
                'source' => 'baseline',
                'notes' => 'Initial truck baseline KM',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('vehicle_odometer_readings', [
            'vehicle_id' => $vehicle->id,
            'odometer' => $baselineOdometer,
            'source' => 'baseline',
            'notes' => 'Initial truck baseline KM',
        ]);
    }

    public function test_get_reading_history()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();

        // Create multiple readings
        for ($i = 1; $i <= 5; $i++) {
            app(\App\Services\VehicleOdometerService::class)->updateOdometer(
                $vehicle,
                $vehicle->odometer + 100 * $i,
                'manual',
                null,
                $this->user->id,
            );
        }

        $history = app(\App\Services\VehicleOdometerService::class)->getReadingHistory($vehicle, 3);

        $this->assertCount(3, $history);
        $this->assertEquals('manual', $history->first()->source->value);
    }

    public function test_odometer_source_enum_sqlite_compatibility()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();

        $reading = app(\App\Services\VehicleOdometerService::class)->updateOdometer(
            $vehicle,
            $vehicle->odometer + 100,
            'manual',
            null,
            $this->user->id,
        );

        // Test that the enum cast works correctly
        $this->assertEquals('manual', $reading->source->value);
        $this->assertEquals('Manual', $reading->source->label());

        // Test that it's stored as string in database
        $this->assertDatabaseHas('vehicle_odometer_readings', [
            'id' => $reading->id,
            'source' => 'manual',
        ]);
    }

    public function test_get_latest_reading()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $initialOdometer = $vehicle->odometer;

        $reading1 = app(\App\Services\VehicleOdometerService::class)->updateOdometer(
            $vehicle,
            $initialOdometer + 100,
            'manual',
            null,
            $this->user->id,
        );

        sleep(1); // Ensure different timestamps

        $reading2 = app(\App\Services\VehicleOdometerService::class)->updateOdometer(
            $vehicle,
            $initialOdometer + 200,
            'manual',
            null,
            $this->user->id,
        );

        $latest = app(\App\Services\VehicleOdometerService::class)->getLatestReading($vehicle);

        $this->assertInstanceOf(VehicleOdometerReading::class, $latest);
        $this->assertEquals($reading2->id, $latest->id);
    }

    public function test_get_latest_odometer()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $initialOdometer = $vehicle->odometer;

        // Before any readings
        $latest = app(\App\Services\VehicleOdometerService::class)->getLatestOdometer($vehicle);
        $this->assertEquals($initialOdometer, $latest);

        // After adding readings
        app(\App\Services\VehicleOdometerService::class)->updateOdometer(
            $vehicle,
            $initialOdometer + 500,
            'manual',
            null,
            $this->user->id,
        );

        $latest = app(\App\Services\VehicleOdometerService::class)->getLatestOdometer($vehicle);
        $this->assertEquals($initialOdometer + 500, $latest);
    }
}
