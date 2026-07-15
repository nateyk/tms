<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleCombinationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->adminUser = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
    }

    public function test_trailer_can_be_created_and_attached_to_power_vehicle(): void
    {
        $powerType = $this->vehicleType('Combination Power', 'power_vehicle');
        $trailerType = $this->vehicleType('Combination Trailer', 'trailer');
        $power = $this->vehicle($powerType, 'power_vehicle', 'COMBO-POWER-001');

        $this->actingAs($this->adminUser)
            ->post(route('fleet.vehicles.store'), [
                'plate_number' => 'COMBO-TRAILER-001',
                'asset_type' => 'trailer',
                'vehicle_type_id' => $trailerType->id,
                'status' => 'active',
                'attached_power_vehicle_id' => $power->id,
            ])
            ->assertRedirect();

        $trailer = Vehicle::query()->where('plate_number', 'COMBO-TRAILER-001')->firstOrFail();

        $this->assertDatabaseHas('vehicle_combinations', [
            'power_vehicle_id' => $power->id,
            'trailer_vehicle_id' => $trailer->id,
            'status' => 'active',
        ]);
    }

    public function test_already_attached_trailer_cannot_be_attached_to_another_power_vehicle(): void
    {
        $powerType = $this->vehicleType('Combination Power', 'power_vehicle');
        $trailerType = $this->vehicleType('Combination Trailer', 'trailer');
        $firstPower = $this->vehicle($powerType, 'power_vehicle', 'COMBO-POWER-002');
        $secondPower = $this->vehicle($powerType, 'power_vehicle', 'COMBO-POWER-003');
        $trailer = $this->vehicle($trailerType, 'trailer', 'COMBO-TRAILER-002');

        $this->actingAs($this->adminUser)
            ->put(route('fleet.vehicles.update', $trailer), [
                'plate_number' => $trailer->plate_number,
                'asset_type' => 'trailer',
                'vehicle_type_id' => $trailerType->id,
                'status' => 'active',
                'attached_power_vehicle_id' => $firstPower->id,
            ])
            ->assertRedirect();

        $this->actingAs($this->adminUser)
            ->put(route('fleet.vehicles.update', $secondPower), [
                'plate_number' => $secondPower->plate_number,
                'asset_type' => 'power_vehicle',
                'vehicle_type_id' => $powerType->id,
                'status' => 'active',
                'attached_trailer_vehicle_id' => $trailer->id,
            ])
            ->assertSessionHasErrors('attached_trailer_vehicle_id');
    }

    public function test_vehicle_index_exposes_attached_vehicle(): void
    {
        $powerType = $this->vehicleType('Combination Power', 'power_vehicle');
        $trailerType = $this->vehicleType('Combination Trailer', 'trailer');
        $power = $this->vehicle($powerType, 'power_vehicle', 'COMBO-POWER-004');
        $trailer = $this->vehicle($trailerType, 'trailer', 'COMBO-TRAILER-004');

        $this->actingAs($this->adminUser)
            ->put(route('fleet.vehicles.update', $power), [
                'plate_number' => $power->plate_number,
                'asset_type' => 'power_vehicle',
                'vehicle_type_id' => $powerType->id,
                'status' => 'active',
                'attached_trailer_vehicle_id' => $trailer->id,
            ])
            ->assertRedirect();

        $this->actingAs($this->adminUser)
            ->get(route('fleet.vehicles.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('fleet/vehicles/index')
                ->where('vehicles.data', fn ($rows) => collect($rows)->contains(
                    fn (array $row) => $row['id'] === $power->id
                        && $row['attached_vehicle_id'] === $trailer->id
                        && $row['attached_vehicle_role'] === 'Trailer'
                ))
            );
    }

    public function test_vehicle_create_page_exposes_free_trailers_for_power_vehicle_attachment(): void
    {
        $powerType = $this->vehicleType('Combination Power Create', 'power_vehicle');
        $trailerType = $this->vehicleType('Combination Trailer Create', 'trailer');
        $trailer = $this->vehicle($trailerType, 'trailer', 'COMBO-TRAILER-005');

        $this->actingAs($this->adminUser)
            ->get(route('fleet.vehicles.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('fleet/vehicles/create')
                ->where('vehicleTypes', fn ($types) => collect($types)->contains(
                    fn (array $type) => $type['id'] === $powerType->id
                        && $type['asset_type'] === 'power_vehicle'
                        && array_key_exists('tyre_count', $type)
                ))
                ->where('attachableTrailers', fn ($trailers) => collect($trailers)->contains(
                    fn (array $option) => $option['id'] === $trailer->id
                ))
            );
    }

    public function test_vehicle_type_must_match_selected_asset_type(): void
    {
        $trailerType = $this->vehicleType('Mismatch Trailer Type', 'trailer');

        $this->actingAs($this->adminUser)
            ->post(route('fleet.vehicles.store'), [
                'plate_number' => 'COMBO-MISMATCH-001',
                'asset_type' => 'power_vehicle',
                'vehicle_type_id' => $trailerType->id,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('vehicle_type_id');
    }

    private function vehicleType(string $name, string $assetType): VehicleType
    {
        return VehicleType::query()->create([
            'name' => $name,
            'asset_type' => $assetType,
            'axle_count' => 2,
            'tyre_count' => 6,
            'status' => 'active',
        ]);
    }

    private function vehicle(VehicleType $vehicleType, string $assetType, string $plateNumber): Vehicle
    {
        return Vehicle::query()->create([
            'plate_number' => $plateNumber,
            'asset_type' => $assetType,
            'vehicle_type_id' => $vehicleType->id,
            'status' => 'active',
            'odometer' => 1000,
        ]);
    }
}
