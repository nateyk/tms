<?php

namespace Database\Seeders;

use App\Enums\AssetType;
use App\Enums\AssignmentAssetType;
use App\Enums\CombinationStatus;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Enums\VehicleStatus;
use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBrand;
use App\Models\TyreSize;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use App\Models\VehicleType;
use App\Services\VehicleTyreLayoutBuilder;
use Illuminate\Database\Seeder;

class TmsSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@menkem.com')->first();

        $store = Store::query()->firstOrCreate(
            ['code' => 'MAIN-STORE'],
            [
                'name' => 'Main Tyre Store',
                'address' => 'Addis Ababa, Ethiopia',
                'is_default' => true,
                'status' => 'active',
            ]
        );

        TyreBrand::query()->firstOrCreate(['name' => 'Michelin'], ['code' => 'MIC', 'status' => 'active']);
        TyreBrand::query()->firstOrCreate(['name' => 'Bridgestone'], ['code' => 'BRI', 'status' => 'active']);
        TyreSize::query()->firstOrCreate(['size_label' => '315/80R22.5'], ['code' => '315-80-225', 'status' => 'active']);

        $layoutBuilder = app(VehicleTyreLayoutBuilder::class);
        $powerLayout = $layoutBuilder->buildLayout(10, 3, 'P');
        $heavyTruckLayout = $layoutBuilder->buildLayout(24, 6, 'P');
        $trailerLayout = $layoutBuilder->buildLayout(12, 3, 'T');
        $rigidLayout = $layoutBuilder->buildLayout(6, 2, 'R');

        $powerType = VehicleType::query()->updateOrCreate(
            ['name' => 'Power Unit 10 Tyres'],
            [
                'asset_type' => AssetType::PowerVehicle->value,
                'axle_count' => 3,
                'tyre_count' => 10,
                'layout_json' => $powerLayout,
                'status' => 'active',
            ]
        );

        $trailerType = VehicleType::query()->updateOrCreate(
            ['name' => 'Trailer 12 Tyres'],
            [
                'asset_type' => AssetType::Trailer->value,
                'axle_count' => 3,
                'tyre_count' => 12,
                'layout_json' => $trailerLayout,
                'status' => 'active',
            ]
        );

        $heavyTruckType = VehicleType::query()->updateOrCreate(
            ['name' => 'Heavy Truck 24 Tyres + 2 Spares'],
            [
                'asset_type' => AssetType::PowerVehicle->value,
                'axle_count' => 6,
                'tyre_count' => 24,
                'layout_json' => $heavyTruckLayout,
                'status' => 'active',
            ]
        );

        VehicleType::query()->updateOrCreate(
            ['name' => 'Rigid Truck 6 Tyres'],
            [
                'asset_type' => AssetType::RigidTruck->value,
                'axle_count' => 2,
                'tyre_count' => 6,
                'layout_json' => $rigidLayout,
                'status' => 'active',
            ]
        );

        $trk001 = Vehicle::query()->firstOrCreate(
            ['vehicle_code' => 'TRK-001'],
            [
                'plate_number' => 'AA-001-TRK',
                'asset_type' => AssetType::PowerVehicle,
                'vehicle_type_id' => $powerType->id,
                'status' => VehicleStatus::Active,
                'odometer' => 125000,
            ]
        );

        $trk008 = Vehicle::query()->firstOrCreate(
            ['vehicle_code' => 'TRK-008'],
            [
                'plate_number' => 'AA-008-TRK',
                'asset_type' => AssetType::PowerVehicle,
                'vehicle_type_id' => $powerType->id,
                'status' => VehicleStatus::Active,
                'odometer' => 98000,
            ]
        );

        $trk024 = Vehicle::query()->firstOrCreate(
            ['vehicle_code' => 'TRK-024'],
            [
                'plate_number' => 'AA-024-HTK',
                'asset_type' => AssetType::PowerVehicle,
                'vehicle_type_id' => $heavyTruckType->id,
                'status' => VehicleStatus::Active,
                'odometer' => 186400,
            ]
        );

        $trl045 = Vehicle::query()->firstOrCreate(
            ['vehicle_code' => 'TRL-045'],
            [
                'plate_number' => 'AA-045-TRL',
                'asset_type' => AssetType::Trailer,
                'vehicle_type_id' => $trailerType->id,
                'status' => VehicleStatus::Active,
            ]
        );

        VehicleCombination::query()->firstOrCreate(
            [
                'power_vehicle_id' => $trk001->id,
                'trailer_vehicle_id' => $trl045->id,
                'status' => CombinationStatus::Active,
            ],
            [
                'attached_date' => now()->subMonths(2)->toDateString(),
                'odometer_at_attach' => 120000,
                'attached_by' => $admin->id,
            ]
        );

        $brand = TyreBrand::query()->first();
        $size = TyreSize::query()->first();

        for ($i = 1; $i <= 60; $i++) {
            $code = sprintf('TYR-%04d', $i);
            Tyre::query()->firstOrCreate(
                ['tyre_code' => $code],
                [
                    'serial_number' => 'SN-'.strtoupper($code),
                    'brand_id' => $brand?->id,
                    'size_id' => $size?->id,
                    'purchase_date' => now()->subYear()->toDateString(),
                    'purchase_price' => 45000 + ($i * 100),
                    'initial_tread_depth' => 16.0,
                    'current_tread_depth' => 14.5 - ($i * 0.1),
                    'source' => TyreSource::PurchasedNewTyre,
                    'current_location_type' => TyreLocationType::Store,
                    'current_location_id' => $store->id,
                    'status' => TyreStatus::Available,
                ]
            );
        }

        $this->assignTyresToVehicle($trk001, AssignmentAssetType::PowerVehicle, 6, $admin?->id);
        $this->assignTyresToVehicle($trl045, AssignmentAssetType::Trailer, 8, $admin?->id, 7);
        $this->assignTyresToVehicle($trk024, AssignmentAssetType::PowerVehicle, 24, $admin?->id, 21);
    }

    private function assignTyresToVehicle(
        Vehicle $vehicle,
        AssignmentAssetType $assetType,
        int $count,
        ?int $userId,
        int $tyreOffset = 1
    ): void {
        $tyres = Tyre::query()
            ->orderBy('tyre_code')
            ->skip($tyreOffset - 1)
            ->take($count)
            ->get();

        $layout = $vehicle->vehicleType?->positions() ?? [];
        $positionCodes = array_column($layout, 'code');

        foreach ($tyres as $index => $tyre) {
            $position = $positionCodes[$index] ?? 'P'.($index + 1);

            TyreAssignment::query()->updateOrCreate(
                [
                    'asset_id' => $vehicle->id,
                    'position_code' => $position,
                    'status' => TyreAssignmentStatus::Active,
                ],
                [
                    'tyre_id' => $tyre->id,
                    'asset_type' => $assetType,
                    'installed_date' => now()->subMonths(1)->toDateString(),
                    'installed_odometer' => $vehicle->odometer ?? 0,
                    'installed_by' => $userId,
                ]
            );

            $locationType = $assetType === AssignmentAssetType::Trailer
                ? TyreLocationType::Trailer
                : TyreLocationType::PowerVehicle;

            $tyre->update([
                'current_location_type' => $locationType,
                'current_location_id' => $vehicle->id,
                'current_position_code' => $position,
                'status' => TyreStatus::Active,
            ]);
        }
    }
}
