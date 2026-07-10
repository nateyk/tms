<?php

namespace Tests\Feature;

use App\Enums\AssetType;
use App\Enums\AssignmentAssetType;
use App\Enums\MovementType;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Enums\VehicleStatus;
use App\Enums\VoucherStatus;
use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreMovement;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TyreVehiclePlateDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_tyre_current_vehicle_display_includes_vehicle_code_and_plate(): void
    {
        $vehicle = $this->vehicle('TRK-001', 'ET-3-A20001', AssetType::PowerVehicle);
        $tyre = $this->tyre([
            'current_location_type' => TyreLocationType::PowerVehicle,
            'current_location_id' => $vehicle->id,
        ]);

        TyreAssignment::query()->create([
            'tyre_id' => $tyre->id,
            'asset_type' => AssignmentAssetType::PowerVehicle,
            'asset_id' => $vehicle->id,
            'position_code' => 'P1',
            'installed_date' => now()->toDateString(),
            'status' => TyreAssignmentStatus::Active,
        ]);

        $this->assertSame('TRK-001 / ET-3-A20001', $tyre->fresh()->currentVehiclePlateDisplay());
    }

    public function test_movement_location_display_includes_vehicle_plate_and_store_name(): void
    {
        $user = User::factory()->create();
        $tyre = $this->tyre();
        $vehicle = $this->vehicle('TRL-045', 'ET-3-TRAILER-45', AssetType::Trailer);
        $store = Store::query()->create([
            'code' => 'MAIN',
            'name' => 'Main Store',
        ]);

        $movement = TyreMovement::query()->create([
            'movement_no' => 'MOV-TEST-PLATE',
            'movement_type' => MovementType::VehicleToStore,
            'tyre_id' => $tyre->id,
            'from_location_type' => TyreLocationType::Trailer,
            'from_location_id' => $vehicle->id,
            'from_position_code' => 'T1',
            'to_location_type' => TyreLocationType::Store,
            'to_location_id' => $store->id,
            'movement_date' => now()->toDateString(),
            'status' => VoucherStatus::Draft,
            'prepared_by' => $user->id,
        ]);

        $this->assertSame('TRL-045 / ET-3-TRAILER-45', $movement->fromLocationDisplay());
        $this->assertSame('MAIN - Main Store', $movement->toLocationDisplay());
    }

    protected function tyre(array $attributes = []): Tyre
    {
        return Tyre::query()->create(array_merge([
            'tyre_code' => fake()->unique()->bothify('TYR-####'),
            'serial_number' => fake()->unique()->bothify('SN-####'),
            'source' => TyreSource::PurchasedNewTyre,
            'current_location_type' => TyreLocationType::Store,
            'status' => TyreStatus::Available,
        ], $attributes));
    }

    protected function vehicle(string $code, string $plate, AssetType $assetType): Vehicle
    {
        $type = VehicleType::query()->create([
            'name' => $code.' type',
            'asset_type' => $assetType,
            'axle_count' => 2,
        ]);

        return Vehicle::query()->create([
            'vehicle_code' => $code,
            'plate_number' => $plate,
            'asset_type' => $assetType,
            'vehicle_type_id' => $type->id,
            'status' => VehicleStatus::Active,
        ]);
    }
}
