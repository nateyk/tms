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

class CurrentFleetAuditSeeder extends Seeder
{
    private const AUDIT_DATE = '2026-07-07';
    private const EXPECTED_LIFE_KM = 80000;

    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, FleetOperationalDefaultsSeeder::class]);

        DB::transaction(function (): void {
            $admin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
            $powerType = VehicleType::query()
                ->where('name', 'Heavy truck - 24 tyres (6 axles + W/X spares)')
                ->firstOrFail();
            $trailerType = $this->trailerType();
            $size = TyreSize::query()->where('size_label', '315/80R22.5')->firstOrFail();

            foreach ($this->audits() as $audit) {
                $power = $this->vehicle($audit['power'], AssetType::PowerVehicle, $powerType, $audit['km'], $admin->id);
                $trailer = $this->vehicle($audit['trailer'], AssetType::Trailer, $trailerType, $audit['km'], $admin->id);

                VehicleCombination::query()->updateOrCreate(
                    ['power_vehicle_id' => $power->id, 'trailer_vehicle_id' => $trailer->id],
                    [
                        'attached_date' => self::AUDIT_DATE,
                        'odometer_at_attach' => $audit['km'],
                        'status' => CombinationStatus::Active,
                        'attached_by' => $admin->id,
                        'approved_by' => $admin->id,
                        'notes' => 'Current combination imported from the tyre audit sheet.',
                    ],
                );

                $this->recordVehicleKm($power, $audit['km'], $admin->id);
                $this->recordVehicleKm($trailer, $audit['km'], $admin->id);
                $this->clearStaleFitments($power, $trailer, $audit['rows'], $audit['km']);

                foreach ($audit['rows'] as $row) {
                    $this->importTyre($power, $trailer, $size, $admin, $audit['km'], $row);
                }
            }
        });
    }

    /** @param array{power: string, trailer: string, km: int, rows: list<array{position: string, brand: string, serial: string, percentage: int}>} $audit */
    private function clearStaleFitments(Vehicle $power, Vehicle $trailer, array $rows, int $odometer): void
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
                $this->removeAssignment($assignment, $odometer);
            }
        }
    }

    /** @param array{position: string, brand: string, serial: string, percentage: int} $row */
    private function importTyre(Vehicle $power, Vehicle $trailer, TyreSize $size, User $admin, int $odometer, array $row): void
    {
        $owner = $row['position'] <= 'J' ? $power : $trailer;
        $assetType = $owner->isTrailer() ? AssignmentAssetType::Trailer : AssignmentAssetType::PowerVehicle;
        $locationType = $owner->isTrailer() ? TyreLocationType::Trailer : TyreLocationType::PowerVehicle;
        $brand = $this->brand($row['brand']);
        $tyre = Tyre::query()->where('serial_number', $row['serial'])->first();

        if (! $tyre) {
            $tyre = Tyre::query()->create([
                'tyre_code' => $this->nextTyreCode(),
                'serial_number' => $row['serial'],
                'brand_id' => $brand->id,
                'size_id' => $size->id,
                'pattern' => 'Imported fleet audit',
                'supplier' => 'Existing fleet fitment',
                'initial_tread_depth' => 20,
                'source' => TyreSource::ExistingVehicle,
                'status' => TyreStatus::Active,
            ]);
        }

        foreach ($tyre->assignments()->where('status', TyreAssignmentStatus::Active)->get() as $active) {
            if ((int) $active->asset_id !== $owner->id || $active->position_code !== $row['position']) {
                $this->removeAssignment($active, $odometer);
            }
        }

        $positionAssignment = TyreAssignment::query()
            ->where('asset_id', $owner->id)
            ->where('asset_type', $assetType)
            ->where('position_code', $row['position'])
            ->where('status', TyreAssignmentStatus::Active)
            ->first();

        if ($positionAssignment && $positionAssignment->tyre_id !== $tyre->id) {
            $this->removeAssignment($positionAssignment, $odometer);
        }

        TyreAssignment::query()->updateOrCreate(
            [
                'asset_type' => $assetType,
                'asset_id' => $owner->id,
                'position_code' => $row['position'],
                'status' => TyreAssignmentStatus::Active,
            ],
            [
                'tyre_id' => $tyre->id,
                'installed_date' => self::AUDIT_DATE,
                'installed_odometer' => $odometer,
                'installed_by' => $admin->id,
                'notes' => 'Current fitment imported from the tyre audit sheet.',
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
            'notes' => 'Current fitment imported from the tyre audit sheet.',
        ]);

        $inspection = TyreInspection::query()
            ->where('tyre_id', $tyre->id)
            ->whereDate('inspection_date', self::AUDIT_DATE)
            ->where('audit_odometer', $odometer)
            ->first();
        $inspectionAttributes = [
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
                'reason' => 'Current fleet audit import',
                'notes' => 'Condition percentage imported from the 7 Jul 2026 audit sheet.',
            ];

        if ($inspection) {
            $inspection->update($inspectionAttributes);
        } else {
            TyreInspection::query()->create(array_merge([
                'tyre_id' => $tyre->id,
                'inspection_date' => self::AUDIT_DATE,
                'audit_odometer' => $odometer,
            ], $inspectionAttributes));
        }
    }

    private function removeAssignment(TyreAssignment $assignment, int $odometer): void
    {
        $assignment->update([
            'status' => TyreAssignmentStatus::Removed,
            'removed_date' => self::AUDIT_DATE,
            'removed_odometer' => max($odometer, (int) ($assignment->installed_odometer ?? $odometer)),
            'km_used' => max(0, $odometer - (int) ($assignment->installed_odometer ?? $odometer)),
            'notes' => 'Closed while importing the current tyre audit fitment.',
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

    private function vehicle(string $plate, AssetType $assetType, VehicleType $type, int $odometer, int $adminId): Vehicle
    {
        $vehicle = Vehicle::query()->firstOrCreate(
            ['plate_number' => $plate],
            [
                'asset_type' => $assetType,
                'vehicle_type_id' => $type->id,
                'status' => VehicleStatus::Active,
                'odometer' => $odometer,
                'notes' => 'Imported from the 7 Jul 2026 current tyre audit sheet.',
            ],
        );

        $vehicle->forceFill([
            'asset_type' => $assetType,
            'vehicle_type_id' => $type->id,
            'status' => VehicleStatus::Active,
            'odometer' => $odometer,
            'odometer_last_updated_at' => self::AUDIT_DATE.' 00:00:00',
            'odometer_last_updated_by' => $adminId,
        ])->save();

        return $vehicle;
    }

    private function recordVehicleKm(Vehicle $vehicle, int $odometer, int $adminId): void
    {
        VehicleOdometerReading::query()->updateOrCreate(
            ['vehicle_id' => $vehicle->id, 'odometer' => $odometer, 'source' => OdometerReadingSource::Import],
            [
                'reading_date' => self::AUDIT_DATE,
                'recorded_by' => $adminId,
                'notes' => 'Imported from the 7 Jul 2026 current tyre audit sheet.',
            ],
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

    /** @return list<array{power: string, trailer: string, km: int, rows: list<array{position: string, brand: string, serial: string, percentage: int}>}> */
    private function audits(): array
    {
        return [
            $this->audit('ET-3-A00765', 'ET-3-34969', 254529, 'A|TRIANGLE|RF05131Q209|60;B|TRIANGLE|RF05042M201|60;C|TRIANGLE|KF03095L910|70;D|TRIANGLE|KF03095C313|70;E|TRIANGLE|KF03086L912|70;F|TRIANGLE|KF03017N711|70;G|TRIANGLE|KF03097K701|70;H|TRIANGLE|KF03086E207|70;I|TRIANGLE|KF03096E212|70;J|TRIANGLE|KF02236C310|70;K|TRIANGLE|KE07275I504|45;L|TRIANGLE|KE07306A405|45;M|TRIANGLE|E180241|45;N|TRIANGLE|KE10276N810|45;O|TRIANGLE|KE07206R610|45;P|TRIANGLE|KE07267R709|45;Q|TRIANGLE|KE07267J312|45;R|TRIANGLE|KE07275N709|45;S|TRIANGLE|KE07275R904|45;T|TRIANGLE|KE07267R509|45;U|TRIANGLE|E180240|45;V|TRIANGLE|E180249|45;W|DUPRO|J234C23099|0'),
            $this->audit('ET-3-A14762', 'ET-3-36814', 170248, 'A|BLACKHAWK|25C8404654|85;B|BLACKHAWK|25C8446019|85;C|BLACKHAWK|25C0626893|100;D|BLACKHAWK|26C0143303|100;E|BLACKHAWK|25C0603312|100;F|BLACKHAWK|25C0630084|100;G|BLACKHAWK|26C0143311|100;H|BLACKHAWK|26C8092987|100;I|BLACKHAWK|26C8099867|100;J|BLACKHAWK|25C0626932|100;K|TRIANGLE|KE04156H507|40;L|TRIANGLE|KE04156R607|40;M|TRIANGLE|KE04156L110|45;N|TRIANGLE|KE04157R402|40;O|TRIANGLE|KE07117J305|45;P|TRIANGLE|KE07126E207|45;S|TRIANGLE|KE07116J706|40;T|TRIANGLE|KE07107R606|45;U|TRIANGLE|KE07107R603|45;V|TRIANGLE|KE07107H508|40;W|TRIANGLE|RD12182G409|35;X|TRIANGLE|E651834|25'),
        ];
    }

    /** @return array{power: string, trailer: string, km: int, rows: list<array{position: string, brand: string, serial: string, percentage: int}>} */
    private function audit(string $power, string $trailer, int $km, string $rawRows): array
    {
        return [
            'power' => $power,
            'trailer' => $trailer,
            'km' => $km,
            'rows' => collect(explode(';', $rawRows))->map(function (string $row): array {
                [$position, $brand, $serial, $percentage] = explode('|', $row);

                return ['position' => $position, 'brand' => $brand, 'serial' => $serial, 'percentage' => (int) $percentage];
            })->all(),
        ];
    }
}
