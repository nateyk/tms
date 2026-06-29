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
                'chassis_number' => 'CH-TRK-001',
                'engine_number' => 'EN-TRK-001',
                'manufacture_year' => 2020,
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
                'chassis_number' => 'CH-TRK-008',
                'engine_number' => 'EN-TRK-008',
                'manufacture_year' => 2020,
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
                'chassis_number' => 'CH-TRK-024',
                'engine_number' => 'EN-TRK-024',
                'manufacture_year' => 2024,
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
                'chassis_number' => 'CH-TRL-045',
                'manufacture_year' => 2021,
                'asset_type' => AssetType::Trailer,
                'vehicle_type_id' => $trailerType->id,
                'status' => VehicleStatus::Active,
            ]
        );

        $this->seedExistingFleetAssets($powerType, $trailerType, $admin?->id);

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

    private function seedExistingFleetAssets(VehicleType $powerType, VehicleType $trailerType, ?int $userId): void
    {
        foreach ($this->existingFleetRows() as $row) {
            $power = Vehicle::query()->updateOrCreate(
                ['vehicle_code' => $row['power_plate']],
                [
                    'plate_number' => $row['power_plate'],
                    'engine_number' => $row['engine_number'],
                    'chassis_number' => $row['power_chassis'],
                    'manufacture_year' => $row['power_year'],
                    'asset_type' => AssetType::PowerVehicle,
                    'vehicle_type_id' => $powerType->id,
                    'status' => VehicleStatus::Active,
                    'notes' => trim("Existing fleet import. Driver: {$row['driver']}. Power capacity: {$row['power_capacity_quintal']} quintal. Total capacity: {$row['total_capacity_quintal']} quintal."),
                ]
            );

            $trailer = Vehicle::query()->updateOrCreate(
                ['vehicle_code' => $row['trailer_plate']],
                [
                    'plate_number' => $row['trailer_plate'],
                    'chassis_number' => $row['trailer_chassis'],
                    'engine_number' => null,
                    'manufacture_year' => $row['trailer_year'],
                    'asset_type' => AssetType::Trailer,
                    'vehicle_type_id' => $trailerType->id,
                    'status' => VehicleStatus::Active,
                    'notes' => trim("Existing fleet import. Driver: {$row['driver']}. Trailer capacity: {$row['trailer_capacity_quintal']} quintal. Total capacity: {$row['total_capacity_quintal']} quintal."),
                ]
            );

            VehicleCombination::query()->updateOrCreate(
                [
                    'power_vehicle_id' => $power->id,
                    'trailer_vehicle_id' => $trailer->id,
                    'status' => CombinationStatus::Active,
                ],
                [
                    'attached_date' => now()->toDateString(),
                    'attached_by' => $userId,
                    'approved_by' => $userId,
                    'notes' => 'Existing fleet pairing imported from source workbook.',
                ]
            );
        }
    }

    /**
     * @return list<array{
     *     power_plate: string,
     *     engine_number: string,
     *     power_chassis: string,
     *     trailer_plate: string,
     *     trailer_chassis: string,
     *     power_year: int,
     *     trailer_year: int,
     *     power_capacity_quintal: int,
     *     trailer_capacity_quintal: int,
     *     total_capacity_quintal: int,
     *     driver: string
     * }>
     */
    private function existingFleetRows(): array
    {
        return [
            ['power_plate' => 'ኢት-3-A00765', 'engine_number' => 'WD61547*170107015817', 'power_chassis' => 'LZZ5BLSF3HN195982', 'trailer_plate' => 'ኢት-3-34969', 'trailer_chassis' => 'LA99FRJ37N0LFY971', 'power_year' => 2017, 'trailer_year' => 2022, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'ጠብቀው አንዱአለም'],
            ['power_plate' => 'ኢት-3-A14761', 'engine_number' => 'WD615.47210807031467', 'power_chassis' => 'LZZ5BLSF7MN940307', 'trailer_plate' => 'ኢት-3-34051', 'trailer_chassis' => 'LA99403X7M0JYJ019', 'power_year' => 2021, 'trailer_year' => 2021, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'ያሬድ በሻዉረድ'],
            ['power_plate' => 'ኢት-3-A14762', 'engine_number' => 'WD615.47*210807031447*', 'power_chassis' => 'LZZ5BLSF3MN940305', 'trailer_plate' => 'ኢት-3-34054', 'trailer_chassis' => 'LA99403X7M0JYJ020', 'power_year' => 2021, 'trailer_year' => 2021, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'ፋንታ በቀለ'],
            ['power_plate' => 'ኢት-3-A14763', 'engine_number' => 'WD615.47210807032387', 'power_chassis' => 'LZZ5BLSF9MN940308', 'trailer_plate' => 'ኢት-3-34052', 'trailer_chassis' => 'LA99403X7M0JYJ018', 'power_year' => 2021, 'trailer_year' => 2021, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'እድሜአለም ንጉሴ'],
            ['power_plate' => 'ኢት-3-A14766', 'engine_number' => 'WD615.47*210807031437', 'power_chassis' => 'LZZ5BLSF5MN940306', 'trailer_plate' => 'ኢት-3-34055', 'trailer_chassis' => 'LA99403X7M0JYJ021', 'power_year' => 2021, 'trailer_year' => 2021, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => '-'],
            ['power_plate' => 'ኢት-3-A17806', 'engine_number' => 'WD615.47220507043177', 'power_chassis' => 'LZZ5BLSF5NW070908', 'trailer_plate' => 'ኢት-3-34423', 'trailer_chassis' => 'LA99FRA32N0LFY036', 'power_year' => 2022, 'trailer_year' => 2022, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'አለማየሁ ሀይሉ'],
            ['power_plate' => 'ኢት-3-A17807', 'engine_number' => 'WD615.472205070431147', 'power_chassis' => 'LZZ5BLSF7NW070909', 'trailer_plate' => 'ኢት-3-34424', 'trailer_chassis' => 'LA99FRA34N0LFY037', 'power_year' => 2022, 'trailer_year' => 2022, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'መሀሪ ፀጋይ'],
            ['power_plate' => 'ኢት-3-A17808', 'engine_number' => 'WD615.47220607004827', 'power_chassis' => 'LZZ5BLSF3NW070907', 'trailer_plate' => 'ኢት-3-34425', 'trailer_chassis' => 'LA99FRA30NOLFFY035', 'power_year' => 2022, 'trailer_year' => 2022, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'ዘላለም ጌትነት'],
            ['power_plate' => 'ኢት-3-A17749', 'engine_number' => 'WD615.47220507043167', 'power_chassis' => 'LZZ5BLSF1NW070906', 'trailer_plate' => 'ኢት-3-34422', 'trailer_chassis' => 'LA99FRA36N0LFY038', 'power_year' => 2022, 'trailer_year' => 2022, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'ዮናስ ተካ'],
            ['power_plate' => 'ኢት-3-A21632', 'engine_number' => 'WD615.47*2107070030097', 'power_chassis' => 'LZZ5BLSFOMA908614', 'trailer_plate' => 'ኢት-3-36811', 'trailer_chassis' => 'LA99FRA3XNOLFY950', 'power_year' => 2021, 'trailer_year' => 2022, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'ገ/ሂዎት አበርሃ'],
            ['power_plate' => 'ኢት-3-A21633', 'engine_number' => 'WD615.47*210707025827', 'power_chassis' => 'LZZ5BLSF2MA908615', 'trailer_plate' => 'ኢት-3-36816', 'trailer_chassis' => 'LA99FRA31NOLFY948', 'power_year' => 2021, 'trailer_year' => 2022, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'ቁምላቸው ተረፈ'],
            ['power_plate' => 'ኢት-3-A21634', 'engine_number' => 'WD615.47*210707032307', 'power_chassis' => 'LZZ5BLSFMA908612', 'trailer_plate' => 'ኢት-3-36812', 'trailer_chassis' => 'LA99FRA33NOLFY949', 'power_year' => 2021, 'trailer_year' => 2022, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'ሙሉጌታ ሲሳይ'],
            ['power_plate' => 'ኢት-3-A21635', 'engine_number' => 'WD615.47*210707030027', 'power_chassis' => 'LZZ5BLSF5MA908611', 'trailer_plate' => 'ኢት-3-36814', 'trailer_chassis' => 'LA99FRA33N0LFY952', 'power_year' => 2021, 'trailer_year' => 2022, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'ቶላዋቅ ካሳሁን'],
            ['power_plate' => 'ኢት-3-A21636', 'engine_number' => 'WD615.47*210407015697', 'power_chassis' => 'LZZ5BLSF7MA868399', 'trailer_plate' => 'ኢት-3-36815', 'trailer_chassis' => 'LA99FRA35N0LFY953', 'power_year' => 2021, 'trailer_year' => 2022, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'ቶፊቅ አባራያ'],
            ['power_plate' => 'ኢት-3-A23019', 'engine_number' => 'WD615.47*210707030037', 'power_chassis' => 'LZZ5BLSF4MA908616', 'trailer_plate' => 'ኢት-3-36813', 'trailer_chassis' => 'LA99FRRA31N0LFY951', 'power_year' => 2021, 'trailer_year' => 2022, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'ማማሩ ይታየው'],
            ['power_plate' => 'ኢት-3-A27036', 'engine_number' => 'WP125400E2011423G048920', 'power_chassis' => 'LZZ5BLSFXPN044975', 'trailer_plate' => 'ኢት-3-34952', 'trailer_chassis' => 'LA99FRA31N0LFY030', 'power_year' => 2023, 'trailer_year' => 2022, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'አዝመራው አዳሙ'],
            ['power_plate' => 'ኢት-3-A27037', 'engine_number' => 'WP125400E201142G048918', 'power_chassis' => 'LZZ5BLSF3PN044977', 'trailer_plate' => 'ኢት-3-34951', 'trailer_chassis' => 'LA99FRA33N0LFY031', 'power_year' => 2023, 'trailer_year' => 2022, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'ዳንኤል መንገሻ'],
            ['power_plate' => 'ኢት-3-A27049', 'engine_number' => 'WP125400E2011423G048923', 'power_chassis' => 'LZZ5BSF1PN044976', 'trailer_plate' => 'ኢት-3-35766', 'trailer_chassis' => 'LA99FRA38NOLFY963', 'power_year' => 2023, 'trailer_year' => 2022, 'power_capacity_quintal' => 157, 'trailer_capacity_quintal' => 226, 'total_capacity_quintal' => 383, 'driver' => 'ረዲኢ ገብረመድህን'],
        ];
    }
}
