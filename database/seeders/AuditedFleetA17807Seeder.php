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

class AuditedFleetA17807Seeder extends Seeder
{
    private const AUDIT_DATE = '2026-07-07';
    private const AUDIT_ODOMETER = 152044;
    private const EXPECTED_LIFE_KM = 80000;

    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, FleetOperationalDefaultsSeeder::class]);

        DB::transaction(function (): void {
            $admin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
            $powerType = VehicleType::query()->where('name', 'Heavy truck - 24 tyres (6 axles + W/X spares)')->firstOrFail();
            $trailerType = $this->trailerType();
            $size = TyreSize::query()->where('size_label', '315/80R22.5')->firstOrFail();
            $power = $this->vehicle('ET-3-A17807', AssetType::PowerVehicle, $powerType, $admin->id);
            $trailer = $this->vehicle('ET-03 34424', AssetType::Trailer, $trailerType, $admin->id);

            VehicleCombination::query()->updateOrCreate(
                ['power_vehicle_id' => $power->id, 'trailer_vehicle_id' => $trailer->id],
                [
                    'attached_date' => self::AUDIT_DATE,
                    'odometer_at_attach' => self::AUDIT_ODOMETER,
                    'status' => CombinationStatus::Active,
                    'attached_by' => $admin->id,
                    'approved_by' => $admin->id,
                    'notes' => 'Imported from the 7 Jul 2026 ET-3-A17807 / ET-03 34424 tyre audit sheet.',
                ],
            );

            foreach ([$power, $trailer] as $vehicle) {
                VehicleOdometerReading::query()->updateOrCreate(
                    ['vehicle_id' => $vehicle->id, 'odometer' => self::AUDIT_ODOMETER, 'source' => OdometerReadingSource::Import],
                    [
                        'reading_date' => self::AUDIT_DATE,
                        'recorded_by' => $admin->id,
                        'notes' => 'Imported from the 7 Jul 2026 ET-3-A17807 / ET-03 34424 tyre audit sheet.',
                    ],
                );
            }

            $rows = $this->tyres();
            $this->clearStaleFitments($power, $trailer, $rows);

            foreach ($rows as $row) {
                $owner = $row['position'] <= 'J' ? $power : $trailer;
                $assetType = $owner->isTrailer() ? AssignmentAssetType::Trailer : AssignmentAssetType::PowerVehicle;
                $locationType = $owner->isTrailer() ? TyreLocationType::Trailer : TyreLocationType::PowerVehicle;
                $brand = $this->brand($row['brand']);
                $tyre = Tyre::query()->firstOrCreate(
                    ['serial_number' => $row['serial']],
                    [
                        'tyre_code' => $this->nextTyreCode(),
                        'brand_id' => $brand->id,
                        'size_id' => $size->id,
                        'pattern' => 'Imported fleet audit',
                        'supplier' => 'Existing fleet fitment',
                        'initial_tread_depth' => 20,
                        'source' => TyreSource::ExistingVehicle,
                        'status' => TyreStatus::Active,
                    ],
                );

                foreach ($tyre->assignments()->where('status', TyreAssignmentStatus::Active)->get() as $active) {
                    if ($active->asset_id !== $owner->id || $active->position_code !== $row['position']) {
                        $this->removeAssignment($active);
                    }
                }

                TyreAssignment::query()->updateOrCreate(
                    ['asset_type' => $assetType, 'asset_id' => $owner->id, 'position_code' => $row['position'], 'status' => TyreAssignmentStatus::Active],
                    [
                        'tyre_id' => $tyre->id,
                        'installed_date' => self::AUDIT_DATE,
                        'installed_odometer' => self::AUDIT_ODOMETER,
                        'installed_by' => $admin->id,
                        'notes' => 'Opening fitment imported from the 7 Jul 2026 audit sheet.',
                    ],
                );

                $tyre->update([
                    'brand_id' => $brand->id,
                    'size_id' => $size->id,
                    'current_tread_depth' => $this->treadDepth($row['percentage']),
                    'current_location_type' => $locationType,
                    'current_location_id' => $owner->id,
                    'current_position_code' => $row['position'],
                    'status' => TyreStatus::Active,
                    'notes' => 'Imported from the 7 Jul 2026 ET-3-A17807 / ET-03 34424 audit sheet.',
                ]);

                TyreBaseline::query()->updateOrCreate(
                    ['tyre_id' => $tyre->id],
                    [
                        'baseline_location_type' => $locationType,
                        'baseline_location_id' => $owner->id,
                        'baseline_position_code' => $row['position'],
                        'baseline_odometer' => self::AUDIT_ODOMETER,
                        'baseline_percentage' => $row['percentage'],
                        'expected_life_km' => self::EXPECTED_LIFE_KM,
                        'baseline_date' => self::AUDIT_DATE,
                        'created_by' => $admin->id,
                        'notes' => 'Opening baseline from audited condition.',
                    ],
                );

                TyreInspection::query()->updateOrCreate(
                    ['tyre_id' => $tyre->id, 'inspection_date' => self::AUDIT_DATE, 'audit_odometer' => self::AUDIT_ODOMETER],
                    [
                        'vehicle_id' => $owner->id,
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
                        'notes' => 'Condition percentage imported from the 7 Jul 2026 audit sheet.',
                    ],
                );
            }
        });
    }

    /** @param list<array{position: string, brand: string, serial: string, percentage: int}> $rows */
    private function clearStaleFitments(Vehicle $power, Vehicle $trailer, array $rows): void
    {
        $wanted = collect($rows)->pluck('serial', 'position')->all();

        foreach (range('A', 'X') as $position) {
            $owner = $position <= 'J' ? $power : $trailer;
            $assignment = TyreAssignment::query()
                ->where('asset_id', $owner->id)
                ->where('asset_type', $owner->isTrailer() ? AssignmentAssetType::Trailer : AssignmentAssetType::PowerVehicle)
                ->where('position_code', $position)
                ->where('status', TyreAssignmentStatus::Active)
                ->first();

            if ($assignment && (($wanted[$position] ?? null) !== $assignment->tyre?->serial_number)) {
                $this->removeAssignment($assignment);
            }
        }
    }

    private function removeAssignment(TyreAssignment $assignment): void
    {
        $assignment->update([
            'status' => TyreAssignmentStatus::Removed,
            'removed_date' => self::AUDIT_DATE,
            'removed_odometer' => max(self::AUDIT_ODOMETER, (int) ($assignment->installed_odometer ?? self::AUDIT_ODOMETER)),
            'km_used' => max(0, self::AUDIT_ODOMETER - (int) ($assignment->installed_odometer ?? self::AUDIT_ODOMETER)),
            'notes' => 'Closed while reconciling the audited fleet fitment.',
        ]);

        $tyre = $assignment->tyre;
        if ($tyre && ! $tyre->assignments()->where('status', TyreAssignmentStatus::Active)->exists()) {
            $tyre->update([
                'current_location_type' => TyreLocationType::Store,
                'current_location_id' => null,
                'current_position_code' => null,
                'status' => TyreStatus::Available,
            ]);
        }
    }

    private function vehicle(string $plate, AssetType $assetType, VehicleType $type, int $adminId): Vehicle
    {
        $vehicle = Vehicle::query()->firstOrCreate(
            ['plate_number' => $plate],
            ['asset_type' => $assetType, 'vehicle_type_id' => $type->id, 'status' => VehicleStatus::Active, 'odometer' => self::AUDIT_ODOMETER],
        );

        $vehicle->forceFill([
            'asset_type' => $assetType,
            'vehicle_type_id' => $type->id,
            'status' => VehicleStatus::Active,
            'odometer' => self::AUDIT_ODOMETER,
            'odometer_last_updated_at' => self::AUDIT_DATE.' 00:00:00',
            'odometer_last_updated_by' => $adminId,
        ])->save();

        return $vehicle;
    }

    private function trailerType(): VehicleType
    {
        $layout = app(VehicleTyreLayoutBuilder::class)->buildLayout(12, 3, 'T');
        foreach ($layout['positions'] as $index => &$position) {
            $code = chr(75 + $index);
            $position['code'] = $code;
            $position['display_code'] = $code;
            $position['label'] = 'Trailer position '.$code;
        }
        unset($position);
        foreach (['W', 'X'] as $code) {
            $layout['positions'][] = ['code' => $code, 'display_code' => $code, 'legacy_code' => null, 'label' => 'Trailer spare '.$code, 'axle' => 4, 'side' => 'center', 'dual' => 'single', 'x' => 440, 'y' => 346];
        }

        return VehicleType::query()->updateOrCreate(
            ['name' => 'Attached trailer - 12 tyres + W/X spares'],
            ['asset_type' => AssetType::Trailer, 'axle_count' => 3, 'tyre_count' => 14, 'layout_json' => $layout, 'status' => 'active'],
        );
    }

    private function brand(string $name): TyreBrand
    {
        $normalized = strtoupper(trim($name)) === 'DUPRO' ? 'DUPRO' : (str_contains(strtoupper($name), 'BLACK') ? 'Black Hawk' : 'Triangle');

        return TyreBrand::query()->firstOrCreate(
            ['name' => $normalized],
            ['code' => strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $normalized), 0, 3)), 'status' => 'active'],
        );
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

    /** @return list<array{position: string, brand: string, serial: string, percentage: int}> */
    private function tyres(): array
    {
        return [
            ['position' => 'A', 'brand' => 'BLACKHWAK', 'serial' => '26C0133323', 'percentage' => 95],
            ['position' => 'B', 'brand' => 'BLACKHWAK', 'serial' => '26C0133232', 'percentage' => 95],
            ['position' => 'C', 'brand' => 'TRIANGLE', 'serial' => 'KF11155G211', 'percentage' => 85],
            ['position' => 'D', 'brand' => 'TRIANGLE', 'serial' => 'KF10115M914', 'percentage' => 85],
            ['position' => 'E', 'brand' => 'TRIANGLE', 'serial' => 'KF05215J709', 'percentage' => 85],
            ['position' => 'F', 'brand' => 'TRIANGLE', 'serial' => 'KF10116N703', 'percentage' => 85],
            ['position' => 'G', 'brand' => 'TRIANGLE', 'serial' => 'KF10116H707', 'percentage' => 85],
            ['position' => 'H', 'brand' => 'TRIANGLE', 'serial' => 'KF05205I711', 'percentage' => 85],
            ['position' => 'I', 'brand' => 'TRIANGLE', 'serial' => 'KF10116O602', 'percentage' => 85],
            ['position' => 'J', 'brand' => 'TRIANGLE', 'serial' => 'KF10116F910', 'percentage' => 85],
            ['position' => 'K', 'brand' => 'TRIANGLE', 'serial' => 'KC06076K703', 'percentage' => 25],
            ['position' => 'L', 'brand' => 'TRIANGLE', 'serial' => 'CP03283G909', 'percentage' => 25],
            ['position' => 'M', 'brand' => 'TRIANGLE', 'serial' => 'RE02272O311', 'percentage' => 30],
            ['position' => 'N', 'brand' => 'TRIANGLE', 'serial' => 'RE02283B804', 'percentage' => 25],
            ['position' => 'O', 'brand' => 'DUPRO', 'serial' => 'G232A01075', 'percentage' => 30],
            ['position' => 'P', 'brand' => 'TRIANGLE', 'serial' => 'CC05276L213', 'percentage' => 30],
            ['position' => 'Q', 'brand' => 'TRIANGLE', 'serial' => 'KD07165K308', 'percentage' => 20],
            ['position' => 'R', 'brand' => 'TRIANGLE', 'serial' => 'KD07157B604', 'percentage' => 20],
            ['position' => 'S', 'brand' => 'TRIANGLE', 'serial' => 'CP03083I212', 'percentage' => 20],
            ['position' => 'T', 'brand' => 'TRIANGLE', 'serial' => 'RE06101O414', 'percentage' => 35],
            ['position' => 'U', 'brand' => 'TRIANGLE', 'serial' => 'RE06062D316', 'percentage' => 35],
            ['position' => 'V', 'brand' => 'TRIANGLE', 'serial' => 'BP06302O413', 'percentage' => 20],
            ['position' => 'W', 'brand' => 'TRIANGLE', 'serial' => 'A17032', 'percentage' => 25],
        ];
    }
}
