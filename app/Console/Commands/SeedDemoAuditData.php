<?php

namespace App\Console\Commands;

use App\Enums\AssetType;
use App\Enums\AssignmentAssetType;
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
use App\Models\VehicleOdometerReading;
use App\Models\VehicleType;
use App\Services\VehicleTyreLayoutBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedDemoAuditData extends Command
{
    protected $signature = 'tms:seed-demo-audit-data {--force : Run without confirmation} {--preview : Show records only}';

    protected $description = 'Seed screenshot-based tyre audit demo data with audited remaining percentages';

    private const POSITIONS = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L',
        'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
    ];

    public function handle(): int
    {
        $sets = $this->auditSets();

        $this->info('Screenshot tyre audit demo data');
        foreach ($sets as $set) {
            $this->line(sprintf(
                '- %s/%s | KM %s | %s | %d positions',
                $set['plate'],
                $set['trailer_plate'],
                number_format($set['odometer']),
                $set['audit_date'],
                count($set['tyres']),
            ));
        }

        if ($this->option('preview')) {
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Reset and seed these screenshot demo vehicles?')) {
            return self::FAILURE;
        }

        DB::transaction(function () use ($sets) {
            $admin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
            $vehicleType = $this->vehicleType();
            $size = $this->tyreSize();

            $this->cleanup($sets);

            foreach ($sets as $set) {
                $vehicle = $this->createVehicle($set, $vehicleType, $admin);
                $this->createOdometerHistory($vehicle, $set, $admin);

                foreach ($set['tyres'] as $row) {
                    $this->createTyreRow($vehicle, $set, $row, $size, $admin);
                }

                $this->line(sprintf(
                    'Seeded %s (%s/%s)',
                    $vehicle->vehicle_code,
                    $set['plate'],
                    $set['trailer_plate'],
                ));
            }
        });

        $this->info('Screenshot audit demo data seeded.');
        $this->info('Open /tyres/reading-monitoring and use VH-A14766, VH-A14761, and VH-A21635.');

        return self::SUCCESS;
    }

    private function createVehicle(array $set, VehicleType $vehicleType, User $admin): Vehicle
    {
        return Vehicle::query()->create([
            'vehicle_code' => $set['vehicle_code'],
            'plate_number' => $set['plate'],
            'chassis_number' => 'CH-'.$set['vehicle_code'],
            'engine_number' => 'EN-'.$set['vehicle_code'],
            'manufacture_year' => 2024,
            'asset_type' => AssetType::PowerVehicle,
            'vehicle_type_id' => $vehicleType->id,
            'status' => VehicleStatus::Active,
            'odometer' => $set['odometer'],
            'odometer_last_updated_at' => $set['audit_date'],
            'odometer_last_updated_by' => $admin->id,
            'notes' => 'Screenshot audit demo. Trailer plate: '.$set['trailer_plate'],
        ]);
    }

    private function createTyreRow(Vehicle $vehicle, array $set, array $row, TyreSize $size, User $admin): void
    {
        $hasTyre = filled($row['serial']);
        $brand = $hasTyre ? $this->brand((string) $row['brand']) : null;
        $position = $row['position'];
        $baselineOdometer = $set['odometer'] - (int) round((100 - $set['calculated_remaining']) / 100 * $set['expected_life_km']);
        $tyreCode = $hasTyre
            ? 'AUD-'.$set['vehicle_code'].'-'.$position
            : 'AUD-'.$set['vehicle_code'].'-'.$position.'-MISSING';

        $tyre = Tyre::query()->create([
            'tyre_code' => $tyreCode,
            'serial_number' => $hasTyre ? $row['serial'] : $tyreCode,
            'brand_id' => $brand?->id,
            'size_id' => $size->id,
            'pattern' => $hasTyre ? 'Screenshot Audit' : null,
            'supplier' => 'Menkem screenshot audit',
            'purchase_date' => '2026-01-01',
            'purchase_price' => $hasTyre ? 42000 : 0,
            'invoice_number' => 'AUD-'.$set['vehicle_code'],
            'initial_tread_depth' => $hasTyre ? 20 : null,
            'current_tread_depth' => $this->percentageToTreadDepth($row['percentage']),
            'source' => TyreSource::PurchasedNewTyre,
            'current_location_type' => TyreLocationType::PowerVehicle,
            'current_location_id' => $vehicle->id,
            'current_position_code' => $position,
            'status' => $hasTyre ? TyreStatus::Active : TyreStatus::PendingApproval,
            'notes' => $row['remark'],
        ]);

        TyreAssignment::query()->create([
            'tyre_id' => $tyre->id,
            'asset_type' => AssignmentAssetType::PowerVehicle,
            'asset_id' => $vehicle->id,
            'position_code' => $position,
            'installed_date' => $set['audit_date'],
            'installed_odometer' => $baselineOdometer,
            'km_used' => 0,
            'status' => TyreAssignmentStatus::Active,
            'installed_by' => $admin->id,
            'notes' => 'Screenshot audit active fitment.',
        ]);

        if ($hasTyre) {
            TyreBaseline::query()->create([
                'tyre_id' => $tyre->id,
                'baseline_location_type' => TyreLocationType::PowerVehicle->value,
                'baseline_location_id' => $vehicle->id,
                'baseline_position_code' => $position,
                'baseline_odometer' => $baselineOdometer,
                'baseline_percentage' => 100,
                'expected_life_km' => $set['expected_life_km'],
                'baseline_date' => $set['baseline_date'],
                'created_by' => $admin->id,
                'notes' => 'Baseline kept separate from screenshot audit percentage.',
            ]);
        }

        TyreInspection::query()->create([
            'tyre_id' => $tyre->id,
            'inspection_date' => $set['audit_date'],
            'tread_depth' => $this->percentageToTreadDepth($row['percentage']),
            'pressure' => null,
            'audited_remaining_percentage' => $row['percentage'],
            'calculated_remaining_percentage_at_audit' => $hasTyre ? $set['calculated_remaining'] : null,
            'audit_odometer' => $set['odometer'],
            'condition' => $this->conditionFromPercentage($row['percentage'], $row['remark']),
            'inspector' => 'Screenshot Audit',
            'inspected_by' => $admin->id,
            'notes' => $row['remark'],
        ]);
    }

    private function createOdometerHistory(Vehicle $vehicle, array $set, User $admin): void
    {
        $baselineOdometer = $set['odometer'] - (int) round((100 - $set['calculated_remaining']) / 100 * $set['expected_life_km']);
        $step = max(1, intdiv($set['odometer'] - $baselineOdometer, 5));

        VehicleOdometerReading::query()->create([
            'vehicle_id' => $vehicle->id,
            'odometer' => $baselineOdometer,
            'reading_date' => $set['baseline_date'],
            'source' => OdometerReadingSource::Baseline,
            'recorded_by' => $admin->id,
            'notes' => 'Screenshot demo baseline KM.',
        ]);

        for ($i = 1; $i <= 4; $i++) {
            VehicleOdometerReading::query()->create([
                'vehicle_id' => $vehicle->id,
                'odometer' => $baselineOdometer + ($step * $i),
                'reading_date' => date('Y-m-d', strtotime($set['baseline_date'].' +'.$i.' days')),
                'source' => OdometerReadingSource::Manual,
                'recorded_by' => $admin->id,
                'notes' => 'Screenshot demo odometer history.',
            ]);
        }

        VehicleOdometerReading::query()->create([
            'vehicle_id' => $vehicle->id,
            'odometer' => $set['odometer'],
            'reading_date' => $set['audit_date'],
            'source' => OdometerReadingSource::Manual,
            'recorded_by' => $admin->id,
            'notes' => 'Screenshot audit KM reading.',
        ]);
    }

    private function cleanup(array $sets): void
    {
        $codes = collect($sets)->pluck('vehicle_code')->all();
        $vehicleIds = Vehicle::query()->whereIn('vehicle_code', $codes)->withTrashed()->pluck('id');
        $tyreIds = Tyre::query()
            ->where(function ($query) use ($codes) {
                foreach ($codes as $code) {
                    $query->orWhere('tyre_code', 'like', 'AUD-'.$code.'-%');
                }
            })
            ->withTrashed()
            ->pluck('id');

        if ($tyreIds->isNotEmpty()) {
            TyreInspection::query()->whereIn('tyre_id', $tyreIds)->delete();
            TyreBaseline::query()->whereIn('tyre_id', $tyreIds)->delete();
            TyreAssignment::query()->whereIn('tyre_id', $tyreIds)->delete();
            Tyre::query()->whereIn('id', $tyreIds)->withTrashed()->forceDelete();
        }

        if ($vehicleIds->isNotEmpty()) {
            VehicleOdometerReading::query()->whereIn('vehicle_id', $vehicleIds)->delete();
            Vehicle::query()->whereIn('id', $vehicleIds)->withTrashed()->forceDelete();
        }
    }

    private function brand(string $name): TyreBrand
    {
        $normalized = strtoupper(trim($name));

        return TyreBrand::query()->firstOrCreate(
            ['name' => $normalized],
            ['code' => substr(preg_replace('/[^A-Z0-9]/', '', $normalized), 0, 12), 'status' => 'active'],
        );
    }

    private function tyreSize(): TyreSize
    {
        return TyreSize::query()->firstOrCreate(
            ['size_label' => '315/80R22.5'],
            ['code' => '315-80-225', 'status' => 'active'],
        );
    }

    private function vehicleType(): VehicleType
    {
        $layout = app(VehicleTyreLayoutBuilder::class)->buildLayout(24, 6, 'P');

        return VehicleType::query()->firstOrCreate(
            ['name' => 'Heavy Truck 24 Tyres + 2 Spares'],
            [
                'asset_type' => AssetType::PowerVehicle->value,
                'axle_count' => 6,
                'tyre_count' => 24,
                'layout_json' => $layout,
                'status' => 'active',
            ],
        );
    }

    private function percentageToTreadDepth(null|int|float $percentage): ?float
    {
        return $percentage === null ? null : round(((float) $percentage / 100) * 20, 2);
    }

    private function conditionFromPercentage(null|int|float $percentage, ?string $remark): string
    {
        if ($remark === 'NOT FOUND' || $remark === 'NO NUMBER') {
            return $remark;
        }

        if ($percentage === null) {
            return 'Not audited';
        }

        return match (true) {
            $percentage >= 80 => 'Good',
            $percentage >= 50 => 'Watch',
            $percentage >= 30 => 'Low',
            default => 'End of Life',
        };
    }

    private function auditSets(): array
    {
        return [
            [
                'vehicle_code' => 'VH-A14766',
                'plate' => 'ET-3-A14766',
                'trailer_plate' => 'ET-3-34055',
                'odometer' => 170416,
                'audit_date' => '2026-07-07',
                'baseline_date' => '2026-07-02',
                'expected_life_km' => 100000,
                'calculated_remaining' => 90,
                'tyres' => [
                    ['position' => 'A', 'brand' => 'TRIANGLE', 'serial' => 'RF05022U109', 'percentage' => 60, 'remark' => null],
                    ['position' => 'B', 'brand' => 'TRIANGLE', 'serial' => 'RF05122I715', 'percentage' => 60, 'remark' => null],
                    ['position' => 'C', 'brand' => 'TRIANGLE', 'serial' => 'KF03256K501', 'percentage' => 90, 'remark' => null],
                    ['position' => 'D', 'brand' => 'TRIANGLE', 'serial' => 'KF03225F503', 'percentage' => 90, 'remark' => null],
                    ['position' => 'E', 'brand' => 'TRIANGLE', 'serial' => 'KF03257F508', 'percentage' => 90, 'remark' => null],
                    ['position' => 'F', 'brand' => 'TRIANGLE', 'serial' => 'KF03225N704', 'percentage' => 90, 'remark' => null],
                    ['position' => 'G', 'brand' => 'TRIANGLE', 'serial' => 'KF09195J501', 'percentage' => 90, 'remark' => null],
                    ['position' => 'H', 'brand' => 'TRIANGLE', 'serial' => 'KF03236J705', 'percentage' => 90, 'remark' => null],
                    ['position' => 'I', 'brand' => 'TRIANGLE', 'serial' => 'KF03227M511', 'percentage' => 90, 'remark' => null],
                    ['position' => 'J', 'brand' => 'TRIANGLE', 'serial' => 'KF03226L203', 'percentage' => 90, 'remark' => null],
                    ['position' => 'K', 'brand' => 'TRIANGLE', 'serial' => 'KC06157M406', 'percentage' => 30, 'remark' => null],
                    ['position' => 'L', 'brand' => null, 'serial' => null, 'percentage' => null, 'remark' => 'NOT FOUND'],
                    ['position' => 'M', 'brand' => null, 'serial' => null, 'percentage' => null, 'remark' => 'NOT FOUND'],
                    ['position' => 'N', 'brand' => 'TRIANGLE', 'serial' => 'KB04065P704', 'percentage' => 25, 'remark' => null],
                    ['position' => 'O', 'brand' => null, 'serial' => null, 'percentage' => null, 'remark' => 'NO NUMBER'],
                    ['position' => 'P', 'brand' => null, 'serial' => null, 'percentage' => null, 'remark' => 'NOT FOUND'],
                    ['position' => 'Q', 'brand' => null, 'serial' => null, 'percentage' => null, 'remark' => 'NOT FOUND'],
                    ['position' => 'R', 'brand' => 'TRIANGLE', 'serial' => 'KE04157H807', 'percentage' => 25, 'remark' => null],
                    ['position' => 'S', 'brand' => 'TRIANGLE', 'serial' => 'KC06027D305', 'percentage' => 25, 'remark' => null],
                    ['position' => 'T', 'brand' => null, 'serial' => null, 'percentage' => null, 'remark' => 'NOT FOUND'],
                    ['position' => 'U', 'brand' => 'TRIANGLE', 'serial' => 'E563248', 'percentage' => 25, 'remark' => null],
                    ['position' => 'V', 'brand' => 'TRIANGLE', 'serial' => 'KC06195J302', 'percentage' => 25, 'remark' => null],
                    ['position' => 'W', 'brand' => 'TRIANGLE', 'serial' => 'KE04156I512', 'percentage' => 30, 'remark' => 'TRAILER'],
                    ['position' => 'X', 'brand' => 'DUPRO', 'serial' => 'S104C25090', 'percentage' => 25, 'remark' => 'POWER'],
                ],
            ],
            [
                'vehicle_code' => 'VH-A14761',
                'plate' => 'ET-3-A14761',
                'trailer_plate' => 'ET-3-34051',
                'odometer' => 171742,
                'audit_date' => '2026-07-08',
                'baseline_date' => '2026-07-03',
                'expected_life_km' => 100000,
                'calculated_remaining' => 84.5,
                'tyres' => [
                    ['position' => 'A', 'brand' => 'BLACK HWAK', 'serial' => '25C0874961', 'percentage' => 95, 'remark' => null],
                    ['position' => 'B', 'brand' => 'BLACK HWAK', 'serial' => '25C0768685', 'percentage' => 95, 'remark' => null],
                    ['position' => 'C', 'brand' => 'TRIANGLE', 'serial' => 'KE10127M507', 'percentage' => 50, 'remark' => null],
                    ['position' => 'D', 'brand' => 'TRIANGLE', 'serial' => 'KE10196A407', 'percentage' => 50, 'remark' => null],
                    ['position' => 'E', 'brand' => 'TRIANGLE', 'serial' => 'KE10117J306', 'percentage' => 50, 'remark' => null],
                    ['position' => 'F', 'brand' => 'TRIANGLE', 'serial' => 'KE09295C311', 'percentage' => 50, 'remark' => null],
                    ['position' => 'G', 'brand' => 'TRIANGLE', 'serial' => 'KE10177E207', 'percentage' => 55, 'remark' => null],
                    ['position' => 'H', 'brand' => 'TRIANGLE', 'serial' => 'KE08185L501', 'percentage' => 50, 'remark' => null],
                    ['position' => 'I', 'brand' => 'TRIANGLE', 'serial' => 'KE10235H510', 'percentage' => 50, 'remark' => null],
                    ['position' => 'J', 'brand' => 'TRIANGLE', 'serial' => 'KE10196E208', 'percentage' => 50, 'remark' => null],
                    ['position' => 'K', 'brand' => 'TRIANGLE', 'serial' => 'KE04156L109', 'percentage' => 40, 'remark' => null],
                    ['position' => 'L', 'brand' => 'TRIANGLE', 'serial' => 'KE04156A414', 'percentage' => 40, 'remark' => null],
                    ['position' => 'M', 'brand' => 'TRIANGLE', 'serial' => 'E170328', 'percentage' => 40, 'remark' => null],
                    ['position' => 'N', 'brand' => 'TRIANGLE', 'serial' => 'KE10277L210', 'percentage' => 45, 'remark' => null],
                    ['position' => 'O', 'brand' => 'TRIANGLE', 'serial' => 'RD12182M309', 'percentage' => 35, 'remark' => null],
                    ['position' => 'P', 'brand' => 'TRIANGLE', 'serial' => 'RD11222O810', 'percentage' => 35, 'remark' => null],
                    ['position' => 'Q', 'brand' => 'TRIANGLE', 'serial' => 'KC06206304', 'percentage' => 35, 'remark' => null],
                    ['position' => 'R', 'brand' => 'TRIANGLE', 'serial' => 'KE04157R602', 'percentage' => 35, 'remark' => null],
                    ['position' => 'S', 'brand' => 'TRIANGLE', 'serial' => 'E651836', 'percentage' => 35, 'remark' => null],
                    ['position' => 'T', 'brand' => 'TRIANGLE', 'serial' => 'KC06056C508', 'percentage' => 35, 'remark' => null],
                    ['position' => 'U', 'brand' => 'TRIANGLE', 'serial' => 'KB07235K509', 'percentage' => 35, 'remark' => null],
                    ['position' => 'V', 'brand' => 'TRIANGLE', 'serial' => 'E563249', 'percentage' => 35, 'remark' => null],
                    ['position' => 'W', 'brand' => 'TRIANGLE', 'serial' => 'A17032E', 'percentage' => 30, 'remark' => 'POWER'],
                    ['position' => 'X', 'brand' => 'TRIANGLE', 'serial' => 'KE04157E204', 'percentage' => 40, 'remark' => 'TRAILER'],
                ],
            ],
            [
                'vehicle_code' => 'VH-A21635',
                'plate' => 'ET-3-A21635',
                'trailer_plate' => 'ET-3-34054',
                'odometer' => 86780,
                'audit_date' => '2026-07-08',
                'baseline_date' => '2026-07-03',
                'expected_life_km' => 100000,
                'calculated_remaining' => 92,
                'tyres' => [
                    ['position' => 'A', 'brand' => 'BLACK HAWK', 'serial' => '25A0691139', 'percentage' => 90, 'remark' => null],
                    ['position' => 'B', 'brand' => 'BLACK HAWK', 'serial' => '25C0589787', 'percentage' => 90, 'remark' => null],
                    ['position' => 'C', 'brand' => 'TRIANGLE', 'serial' => 'KH01155O103', 'percentage' => 90, 'remark' => null],
                    ['position' => 'D', 'brand' => 'TRIANGLE', 'serial' => 'KF09177G204', 'percentage' => 90, 'remark' => null],
                    ['position' => 'E', 'brand' => 'TRIANGLE', 'serial' => 'KF09175E104', 'percentage' => 90, 'remark' => null],
                    ['position' => 'F', 'brand' => 'TRIANGLE', 'serial' => 'KF08235J711', 'percentage' => 90, 'remark' => null],
                    ['position' => 'G', 'brand' => 'TRIANGLE', 'serial' => 'KF09206N714', 'percentage' => 90, 'remark' => null],
                    ['position' => 'H', 'brand' => 'TRIANGLE', 'serial' => 'KF09177E111', 'percentage' => 90, 'remark' => null],
                    ['position' => 'I', 'brand' => 'TRIANGLE', 'serial' => 'KF08236E204', 'percentage' => 90, 'remark' => null],
                    ['position' => 'J', 'brand' => 'TRIANGLE', 'serial' => 'KF08237E110', 'percentage' => 90, 'remark' => null],
                    ['position' => 'K', 'brand' => 'TRIANGLE', 'serial' => 'E74538', 'percentage' => 50, 'remark' => null],
                    ['position' => 'L', 'brand' => 'TRIANGLE', 'serial' => 'A170323', 'percentage' => 30, 'remark' => null],
                    ['position' => 'M', 'brand' => 'TRIANGLE', 'serial' => 'KC05127M402', 'percentage' => 40, 'remark' => null],
                    ['position' => 'N', 'brand' => 'TRIANGLE', 'serial' => 'KC03275K701', 'percentage' => 40, 'remark' => null],
                    ['position' => 'O', 'brand' => 'TRIANGLE', 'serial' => 'KB07247K106', 'percentage' => 30, 'remark' => null],
                    ['position' => 'P', 'brand' => 'TRIANGLE', 'serial' => 'KB07245K501', 'percentage' => 30, 'remark' => null],
                    ['position' => 'Q', 'brand' => 'TRIANGLE', 'serial' => 'KD07146P910', 'percentage' => 30, 'remark' => null],
                    ['position' => 'R', 'brand' => 'TRIANGLE', 'serial' => 'E56322E', 'percentage' => 30, 'remark' => 'NOT VISIBLE LETTER'],
                    ['position' => 'S', 'brand' => 'TRIANGLE', 'serial' => 'E563241', 'percentage' => 30, 'remark' => null],
                    ['position' => 'T', 'brand' => 'TRIANGLE', 'serial' => 'J103272', 'percentage' => 30, 'remark' => null],
                    ['position' => 'U', 'brand' => 'TRIANGLE', 'serial' => 'KC05246K708', 'percentage' => 30, 'remark' => null],
                    ['position' => 'V', 'brand' => 'TRIANGLE', 'serial' => 'E180242', 'percentage' => 30, 'remark' => null],
                    ['position' => 'W', 'brand' => 'TRIANGLE', 'serial' => 'KC05076K713', 'percentage' => 29, 'remark' => 'SCORT POWER'],
                    ['position' => 'X', 'brand' => 'TRIANGLE', 'serial' => 'KD07165G103', 'percentage' => 29, 'remark' => 'SCORT TRAILER'],
                ],
            ],
        ];
    }
}
