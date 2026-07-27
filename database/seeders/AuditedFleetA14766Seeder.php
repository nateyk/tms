<?php

namespace Database\Seeders;

use App\Enums\AssetType;
use App\Enums\AssignmentAssetType;
use App\Enums\CombinationStatus;
use App\Enums\OdometerReadingSource;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Enums\VehicleStatus;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBaseline;
use App\Models\TyreBrand;
use App\Models\TyreInspection;
use App\Models\TyreSize;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use App\Models\VehicleOdometerReading;
use App\Models\VehicleType;
use App\Services\VehicleTyreLayoutBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditedFleetA14766Seeder extends Seeder
{
    private const AUDIT_DATE = '2026-07-07';
    private const AUDIT_ODOMETER = 170416;
    private const EXPECTED_LIFE_KM = 80000;

    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            FleetOperationalDefaultsSeeder::class,
        ]);

        DB::transaction(function (): void {
            $admin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
            $powerType = VehicleType::query()
                ->where('name', 'Heavy truck - 24 tyres (6 axles + W/X spares)')
                ->firstOrFail();
            $trailerType = $this->trailerType();
            $brand = TyreBrand::query()->where('name', 'Triangle')->firstOrFail();
            $dupro = TyreBrand::query()->where('name', 'DUPRO')->firstOrFail();
            $size = TyreSize::query()->where('size_label', '315/80R22.5')->firstOrFail();

            $power = $this->powerVehicle($powerType, $admin->id);
            $trailer = $this->trailerVehicle($trailerType);

            VehicleCombination::query()->updateOrCreate(
                [
                    'power_vehicle_id' => $power->id,
                    'trailer_vehicle_id' => $trailer->id,
                ],
                [
                    'attached_date' => self::AUDIT_DATE,
                    'odometer_at_attach' => self::AUDIT_ODOMETER,
                    'status' => CombinationStatus::Active,
                    'attached_by' => $admin->id,
                    'approved_by' => $admin->id,
                    'notes' => 'Imported as an active audited fleet combination.',
                ],
            );

            VehicleOdometerReading::query()->updateOrCreate(
                [
                    'vehicle_id' => $power->id,
                    'odometer' => self::AUDIT_ODOMETER,
                    'source' => OdometerReadingSource::Import,
                ],
                [
                    'reading_date' => self::AUDIT_DATE,
                    'recorded_by' => $admin->id,
                    'notes' => 'Imported from the 7 Jul 2026 tyre audit sheet.',
                ],
            );

            foreach ($this->tyres() as $row) {
                if ($row['serial'] === null) {
                    continue;
                }

                $vehicle = $row['owner'] === 'trailer' ? $trailer : $power;
                $assetType = $row['owner'] === 'trailer'
                    ? AssignmentAssetType::Trailer
                    : AssignmentAssetType::PowerVehicle;
                $locationType = $row['owner'] === 'trailer'
                    ? TyreLocationType::Trailer
                    : TyreLocationType::PowerVehicle;
                $tyreBrand = $row['brand'] === 'DUPRO' ? $dupro : $brand;

                $tyre = Tyre::query()->firstOrCreate(
                    ['serial_number' => $row['serial']],
                    [
                        'tyre_code' => $this->nextTyreCode(),
                        'brand_id' => $tyreBrand->id,
                        'size_id' => $size->id,
                        'pattern' => 'Imported fleet audit',
                        'supplier' => 'Existing fleet fitment',
                        'initial_tread_depth' => 20,
                        'current_tread_depth' => $this->treadDepth($row['percentage']),
                        'source' => TyreSource::ExistingVehicle,
                        'current_location_type' => $locationType,
                        'current_location_id' => $vehicle->id,
                        'current_position_code' => $row['position'],
                        'status' => TyreStatus::Active,
                        'notes' => 'Imported from the ET-3-A14766 / ET-3-34055 tyre audit sheet.',
                    ],
                );

                $tyre->update([
                    'brand_id' => $tyreBrand->id,
                    'size_id' => $size->id,
                    'current_tread_depth' => $this->treadDepth($row['percentage']),
                    'current_location_type' => $locationType,
                    'current_location_id' => $vehicle->id,
                    'current_position_code' => $row['position'],
                    'status' => TyreStatus::Active,
                ]);

                TyreAssignment::query()->updateOrCreate(
                    [
                        'asset_type' => $assetType,
                        'asset_id' => $vehicle->id,
                        'position_code' => $row['position'],
                        'status' => TyreAssignmentStatus::Active,
                    ],
                    [
                        'tyre_id' => $tyre->id,
                        'installed_date' => self::AUDIT_DATE,
                        'installed_odometer' => self::AUDIT_ODOMETER,
                        'installed_by' => $admin->id,
                        'notes' => 'Opening fitment imported from the tyre audit sheet.',
                    ],
                );

                TyreBaseline::query()->updateOrCreate(
                    ['tyre_id' => $tyre->id],
                    [
                        'baseline_location_type' => $locationType,
                        'baseline_location_id' => $vehicle->id,
                        'baseline_position_code' => $row['position'],
                        'baseline_odometer' => self::AUDIT_ODOMETER,
                        'baseline_percentage' => $row['percentage'],
                        'expected_life_km' => self::EXPECTED_LIFE_KM,
                        'baseline_date' => self::AUDIT_DATE,
                        'created_by' => $admin->id,
                        'notes' => 'Opening baseline from audited condition. Historic installation KM was not supplied.',
                    ],
                );

                TyreInspection::query()->updateOrCreate(
                    [
                        'tyre_id' => $tyre->id,
                        'inspection_date' => self::AUDIT_DATE,
                        'audit_odometer' => self::AUDIT_ODOMETER,
                    ],
                    [
                        'vehicle_id' => $vehicle->id,
                        'position_code' => $row['position'],
                        'tread_depth' => $this->treadDepth($row['percentage']),
                        'audited_remaining_percentage' => $row['percentage'],
                        'calculated_remaining_percentage_at_audit' => $row['percentage'],
                        'variance_percentage' => 0,
                        'condition' => $this->condition($row['percentage']),
                        'inspector' => 'Imported tyre audit',
                        'inspected_by' => $admin->id,
                        'audited_by' => $admin->id,
                        'reason' => 'Initial audited fleet import',
                        'notes' => 'Condition percentage imported from the audit sheet.',
                    ],
                );
            }
        });
    }

    private function powerVehicle(VehicleType $type, int $adminId): Vehicle
    {
        $vehicle = Vehicle::query()->firstOrCreate(
            ['plate_number' => 'ET-3-A14766'],
            [
                'chassis_number' => 'LZZ5BLSF5MN940306',
                'engine_number' => 'WD615.47*210807031437',
                'manufacture_year' => 2021,
                'asset_type' => AssetType::PowerVehicle,
                'vehicle_type_id' => $type->id,
                'status' => VehicleStatus::Active,
                'odometer' => self::AUDIT_ODOMETER,
                'notes' => 'Imported from the 7 Jul 2026 audited fleet sheet.',
            ],
        );

        $vehicle->forceFill([
            'vehicle_type_id' => $type->id,
            'status' => VehicleStatus::Active,
            'odometer' => self::AUDIT_ODOMETER,
            'odometer_last_updated_at' => self::AUDIT_DATE.' 00:00:00',
            'odometer_last_updated_by' => $adminId,
        ])->save();

        return $vehicle;
    }

    private function trailerVehicle(VehicleType $type): Vehicle
    {
        return Vehicle::query()->firstOrCreate(
            ['plate_number' => 'ET-3-34055'],
            [
                'chassis_number' => 'LA99403X7M0JYJ021',
                'manufacture_year' => 2021,
                'asset_type' => AssetType::Trailer,
                'vehicle_type_id' => $type->id,
                'status' => VehicleStatus::Active,
                'notes' => 'Attached trailer imported from the 7 Jul 2026 audited fleet sheet.',
            ],
        );
    }

    private function trailerType(): VehicleType
    {
        $layout = app(VehicleTyreLayoutBuilder::class)->buildLayout(12, 3, 'T');
        $codes = range('K', 'V');

        foreach ($layout['positions'] as $index => &$position) {
            $position['code'] = $codes[$index];
            $position['display_code'] = $codes[$index];
            $position['label'] = 'Trailer position '.$codes[$index];
        }
        unset($position);

        $layout['positions'][] = [
            'code' => 'W',
            'display_code' => 'W',
            'legacy_code' => null,
            'label' => 'Trailer spare wheel',
            'axle' => 4,
            'side' => 'center',
            'dual' => 'single',
            'x' => 440,
            'y' => 346,
        ];

        return VehicleType::query()->updateOrCreate(
            ['name' => 'Attached trailer - 12 tyres + W spare'],
            [
                'asset_type' => AssetType::Trailer,
                'axle_count' => 3,
                'tyre_count' => 13,
                'layout_json' => $layout,
                'status' => 'active',
            ],
        );
    }

    /** @return list<array{owner: 'power'|'trailer', position: string, brand: string, serial: string|null, percentage: int}> */
    private function tyres(): array
    {
        return [
            ['owner' => 'power', 'position' => 'A', 'brand' => 'TRIANGLE', 'serial' => 'RF05022U109', 'percentage' => 60],
            ['owner' => 'power', 'position' => 'B', 'brand' => 'TRIANGLE', 'serial' => 'RF05122I715', 'percentage' => 60],
            ['owner' => 'power', 'position' => 'C', 'brand' => 'TRIANGLE', 'serial' => 'KF03256K501', 'percentage' => 90],
            ['owner' => 'power', 'position' => 'D', 'brand' => 'TRIANGLE', 'serial' => 'KF03225F503', 'percentage' => 90],
            ['owner' => 'power', 'position' => 'E', 'brand' => 'TRIANGLE', 'serial' => 'KF03257F508', 'percentage' => 90],
            ['owner' => 'power', 'position' => 'F', 'brand' => 'TRIANGLE', 'serial' => 'KF03225N704', 'percentage' => 90],
            ['owner' => 'power', 'position' => 'G', 'brand' => 'TRIANGLE', 'serial' => 'KF09195J501', 'percentage' => 90],
            ['owner' => 'power', 'position' => 'H', 'brand' => 'TRIANGLE', 'serial' => 'KF03236J705', 'percentage' => 90],
            ['owner' => 'power', 'position' => 'I', 'brand' => 'TRIANGLE', 'serial' => 'KF03227M511', 'percentage' => 90],
            ['owner' => 'power', 'position' => 'J', 'brand' => 'TRIANGLE', 'serial' => 'KF03226L203', 'percentage' => 90],
            ['owner' => 'trailer', 'position' => 'K', 'brand' => 'TRIANGLE', 'serial' => 'KC06157M406', 'percentage' => 30],
            ['owner' => 'trailer', 'position' => 'L', 'brand' => 'TRIANGLE', 'serial' => null, 'percentage' => 0],
            ['owner' => 'trailer', 'position' => 'M', 'brand' => 'TRIANGLE', 'serial' => null, 'percentage' => 0],
            ['owner' => 'trailer', 'position' => 'N', 'brand' => 'TRIANGLE', 'serial' => 'KB04065P704', 'percentage' => 25],
            ['owner' => 'trailer', 'position' => 'O', 'brand' => 'TRIANGLE', 'serial' => null, 'percentage' => 0],
            ['owner' => 'trailer', 'position' => 'P', 'brand' => 'TRIANGLE', 'serial' => null, 'percentage' => 0],
            ['owner' => 'trailer', 'position' => 'Q', 'brand' => 'TRIANGLE', 'serial' => null, 'percentage' => 0],
            ['owner' => 'trailer', 'position' => 'R', 'brand' => 'TRIANGLE', 'serial' => 'KE04157H807', 'percentage' => 25],
            ['owner' => 'trailer', 'position' => 'S', 'brand' => 'TRIANGLE', 'serial' => 'KC06027D305', 'percentage' => 25],
            ['owner' => 'trailer', 'position' => 'T', 'brand' => 'TRIANGLE', 'serial' => null, 'percentage' => 0],
            ['owner' => 'trailer', 'position' => 'U', 'brand' => 'TRIANGLE', 'serial' => 'E563248', 'percentage' => 25],
            ['owner' => 'trailer', 'position' => 'V', 'brand' => 'TRIANGLE', 'serial' => 'KC06195J302', 'percentage' => 25],
            ['owner' => 'trailer', 'position' => 'W', 'brand' => 'TRIANGLE', 'serial' => 'KE04156I512', 'percentage' => 30],
            ['owner' => 'power', 'position' => 'X', 'brand' => 'DUPRO', 'serial' => 'S104C25090', 'percentage' => 25],
        ];
    }

    private function nextTyreCode(): string
    {
        $next = ((int) Tyre::withTrashed()->max('id')) + 1;

        do {
            $code = sprintf('TYR-%04d', $next++);
        } while (Tyre::withTrashed()->where('tyre_code', $code)->exists());

        return $code;
    }

    private function treadDepth(int $percentage): float
    {
        return round(($percentage / 100) * 20, 2);
    }

    private function condition(int $percentage): string
    {
        return match (true) {
            $percentage >= 80 => 'Good',
            $percentage >= 50 => 'Watch',
            $percentage >= 30 => 'Low',
            default => 'End of Life',
        };
    }
}
