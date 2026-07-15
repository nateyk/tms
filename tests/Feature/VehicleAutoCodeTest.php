<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleAutoCodeTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->adminUser = User::query()
            ->where('email', 'admin@menkem.com')
            ->firstOrFail();
    }

    public function test_vehicle_create_generates_vehicle_code_when_not_submitted(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('fleet.vehicles.store'), $this->payload([
                'plate_number' => 'AUTO-PLATE-001',
            ]));

        $response->assertRedirect();

        $vehicle = Vehicle::query()->where('plate_number', 'AUTO-PLATE-001')->firstOrFail();

        $this->assertMatchesRegularExpression('/^TRK-\d{4,}$/', $vehicle->vehicle_code);
    }

    public function test_vehicle_create_ignores_user_submitted_vehicle_code(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('fleet.vehicles.store'), $this->payload([
                'vehicle_code' => 'USER-TYPED-CODE',
                'plate_number' => 'AUTO-PLATE-002',
            ]));

        $response->assertRedirect();

        $vehicle = Vehicle::query()->where('plate_number', 'AUTO-PLATE-002')->firstOrFail();

        $this->assertNotSame('USER-TYPED-CODE', $vehicle->vehicle_code);
        $this->assertMatchesRegularExpression('/^TRK-\d{4,}$/', $vehicle->vehicle_code);
    }

    public function test_vehicle_update_does_not_change_vehicle_code(): void
    {
        $vehicle = Vehicle::query()->create($this->payload([
            'plate_number' => 'AUTO-PLATE-003',
        ]));

        $originalCode = $vehicle->vehicle_code;

        $response = $this->actingAs($this->adminUser)
            ->put(route('fleet.vehicles.update', $vehicle), $this->payload([
                'vehicle_code' => 'CHANGED-BY-USER',
                'plate_number' => 'AUTO-PLATE-003-UPDATED',
            ]));

        $response->assertRedirect();

        $vehicle->refresh();

        $this->assertSame($originalCode, $vehicle->vehicle_code);
        $this->assertSame('AUTO-PLATE-003-UPDATED', $vehicle->plate_number);
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        $vehicleType = VehicleType::query()->where('asset_type', 'power_vehicle')->first()
            ?? VehicleType::query()->firstOrFail();

        return array_merge([
            'vehicle_code' => '',
            'plate_number' => 'AUTO-PLATE',
            'chassis_number' => null,
            'engine_number' => null,
            'asset_type' => $vehicleType->asset_type->value,
            'vehicle_type_id' => $vehicleType->id,
            'status' => 'active',
            'current_location_id' => null,
            'manufacture_year' => null,
            'odometer' => null,
            'notes' => null,
        ], $overrides);
    }
}
