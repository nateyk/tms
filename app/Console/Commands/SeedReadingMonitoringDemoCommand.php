<?php

namespace App\Console\Commands;

use App\Enums\AssetType;
use App\Enums\AssignmentAssetType;
use App\Enums\DisposalReason;
use App\Enums\MovementType;
use App\Enums\OdometerReadingSource;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Enums\VehicleStatus;
use App\Enums\VoucherStatus;
use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBaseline;
use App\Models\TyreBrand;
use App\Models\TyreDisposal;
use App\Models\TyreMovement;
use App\Models\TyreSize;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use App\Models\VehicleOdometerReading;
use App\Models\VehicleType;
use App\Services\VehicleTyreLayoutBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedReadingMonitoringDemoCommand extends Command
{
    protected $signature = 'tms:seed-reading-monitoring-demo';
    protected $description = 'Reset and seed three complete truck demo datasets for reading monitoring manual testing';

    private const DEMO_TRUCKS = [
        [
            'code' => 'DEMO-TRK-01',
            'plate' => 'DEMO-001',
            'baseline_odometer' => 146783,
            'current_odometer' => 146945,
            'brand_offset' => 0,
            'baseline_percentages' => [
                'A' => 100, 'B' => 100, 'C' => 96, 'D' => 96, 'E' => 94, 'F' => 94,
                'G' => 91, 'H' => 91, 'I' => 88, 'J' => 88, 'K' => 84, 'L' => 84,
                'M' => 80, 'N' => 80, 'O' => 76, 'P' => 76, 'Q' => 72, 'R' => 72,
                'S' => 68, 'T' => 68, 'U' => 64, 'V' => 64, 'W' => 100, 'X' => 100,
            ],
        ],
        [
            'code' => 'DEMO-TRK-02',
            'plate' => 'DEMO-002',
            'baseline_odometer' => 171250,
            'current_odometer' => 173875,
            'brand_offset' => 1,
            'baseline_percentages' => [
                'A' => 95, 'B' => 95, 'C' => 90, 'D' => 90, 'E' => 86, 'F' => 86,
                'G' => 82, 'H' => 82, 'I' => 78, 'J' => 78, 'K' => 74, 'L' => 74,
                'M' => 70, 'N' => 70, 'O' => 66, 'P' => 66, 'Q' => 62, 'R' => 62,
                'S' => 58, 'T' => 58, 'U' => 54, 'V' => 54, 'W' => 95, 'X' => 95,
            ],
        ],
        [
            'code' => 'DEMO-TRK-03',
            'plate' => 'DEMO-003',
            'baseline_odometer' => 224100,
            'current_odometer' => 232600,
            'brand_offset' => 2,
            'baseline_percentages' => [
                'A' => 82, 'B' => 82, 'C' => 78, 'D' => 78, 'E' => 74, 'F' => 74,
                'G' => 70, 'H' => 70, 'I' => 66, 'J' => 66, 'K' => 62, 'L' => 62,
                'M' => 58, 'N' => 58, 'O' => 54, 'P' => 54, 'Q' => 50, 'R' => 50,
                'S' => 46, 'T' => 46, 'U' => 42, 'V' => 42, 'W' => 82, 'X' => 82,
            ],
        ],
    ];

    public function handle(): int
    {
        $this->info('Resetting three-truck reading monitoring demo data...');

        DB::beginTransaction();

        try {
            $admin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
            $this->cleanupDemoData();

            $brands = $this->createBrands();
            $size = $this->createSize();
            $store = $this->createStore();
            $vehicleType = $this->createVehicleType();

            foreach (self::DEMO_TRUCKS as $truck) {
                $vehicle = $this->createVehicle($truck, $vehicleType);
                $positions = array_column($vehicleType->positions() ?? [], 'code');
                $tyresByPosition = $this->createTyresForVehicle($vehicle, $positions, $truck, $brands, $size, $store, $admin);

                $this->createVehicleOdometerHistory($vehicle, $truck, $admin);
                $this->createMovementHistory($vehicle, $truck, $tyresByPosition, $store, $admin);
                $this->createDisposalHistory($vehicle, $truck, $brands, $size, $store, $admin);

                $this->line(sprintf(
                    '- %s seeded: baseline %s KM, current %s KM, %d mounted tyres',
                    $vehicle->displayCodeWithPlate(),
                    number_format($truck['baseline_odometer']),
                    number_format($truck['current_odometer']),
                    count($tyresByPosition),
                ));
            }

            DB::commit();

            $this->info("\nDemo reset complete.");
            $this->info('Open /tyres/reading-monitoring and test DEMO-TRK-01, DEMO-TRK-02, DEMO-TRK-03.');
            $this->info('Each truck has tyre baselines, truck baseline KM, five manual odometer updates, movement history, and disposal history.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Demo reset failed: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }

    private function createBrands(): array
    {
        $brands = [];

        foreach (['Michelin', 'Bridgestone', 'Triangle', 'Double Coin', 'Aeolus'] as $index => $name) {
            $brands[] = TyreBrand::query()->updateOrCreate(
                ['name' => $name],
                ['code' => strtoupper(substr($name, 0, 3)).str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT), 'status' => 'active'],
            );
        }

        return $brands;
    }

    private function createSize(): TyreSize
    {
        return TyreSize::query()->updateOrCreate(
            ['size_label' => '315/80R22.5'],
            ['code' => '315-80-225', 'status' => 'active'],
        );
    }

    private function createStore(): Store
    {
        return Store::query()->updateOrCreate(
            ['code' => 'MAIN-STORE'],
            [
                'name' => 'Main Tyre Store',
                'address' => 'Addis Ababa, Ethiopia',
                'is_default' => true,
                'status' => 'active',
            ],
        );
    }

    private function createVehicleType(): VehicleType
    {
        $layout = app(VehicleTyreLayoutBuilder::class)->buildLayout(24, 6, 'P');

        return VehicleType::query()->updateOrCreate(
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

    private function createVehicle(array $truck, VehicleType $vehicleType): Vehicle
    {
        return Vehicle::query()->create([
            'vehicle_code' => $truck['code'],
            'plate_number' => $truck['plate'],
            'chassis_number' => 'CH-'.$truck['code'],
            'engine_number' => 'EN-'.$truck['code'],
            'manufacture_year' => 2024,
            'asset_type' => AssetType::PowerVehicle,
            'vehicle_type_id' => $vehicleType->id,
            'status' => VehicleStatus::Active,
            'odometer' => $truck['current_odometer'],
            'odometer_last_updated_at' => now(),
            'odometer_last_updated_by' => User::query()->where('email', 'admin@menkem.com')->value('id'),
            'notes' => 'Reset demo truck with complete tyre reading data.',
        ]);
    }

    private function createTyresForVehicle(
        Vehicle $vehicle,
        array $positions,
        array $truck,
        array $brands,
        TyreSize $size,
        Store $store,
        User $admin,
    ): array {
        $tyresByPosition = [];

        foreach ($positions as $index => $position) {
            $brand = $brands[($index + $truck['brand_offset']) % count($brands)];
            $baselinePercentage = $truck['baseline_percentages'][$position] ?? 100;
            $isSpare = in_array($position, ['W', 'X'], true);
            $tyreCode = "{$truck['code']}-{$position}";

            $tyre = Tyre::query()->create([
                'tyre_code' => $tyreCode,
                'serial_number' => "SN-{$tyreCode}",
                'brand_id' => $brand->id,
                'size_id' => $size->id,
                'pattern' => $isSpare ? 'Spare Pattern' : 'Long Haul',
                'supplier' => 'Demo Supplier',
                'purchase_date' => now()->subMonths(8)->toDateString(),
                'purchase_price' => 43000 + ($index * 275),
                'invoice_number' => "INV-{$tyreCode}",
                'initial_tread_depth' => 16.0,
                'current_tread_depth' => round(8 + ($baselinePercentage / 12), 2),
                'source' => TyreSource::PurchasedNewTyre,
                'current_location_type' => TyreLocationType::PowerVehicle,
                'current_location_id' => $vehicle->id,
                'current_position_code' => $position,
                'status' => TyreStatus::Active,
                'notes' => 'Reset demo mounted tyre.',
            ]);

            TyreAssignment::query()->create([
                'tyre_id' => $tyre->id,
                'asset_type' => AssignmentAssetType::PowerVehicle,
                'asset_id' => $vehicle->id,
                'position_code' => $position,
                'installed_date' => now()->subDays(25)->toDateString(),
                'installed_odometer' => $truck['baseline_odometer'],
                'km_used' => 0,
                'status' => TyreAssignmentStatus::Active,
                'installed_by' => $admin->id,
                'notes' => 'Reset demo active assignment.',
            ]);

            TyreBaseline::query()->create([
                'tyre_id' => $tyre->id,
                'baseline_location_type' => TyreLocationType::PowerVehicle->value,
                'baseline_location_id' => $vehicle->id,
                'baseline_position_code' => $position,
                'baseline_odometer' => $truck['baseline_odometer'],
                'baseline_percentage' => $baselinePercentage,
                'expected_life_km' => $isSpare ? 90000 : 80000,
                'baseline_date' => now()->subDays(25)->toDateString(),
                'created_by' => $admin->id,
                'notes' => "Reset demo tyre baseline for {$vehicle->vehicle_code} {$position}.",
            ]);

            $tyresByPosition[$position] = $tyre;
        }

        return $tyresByPosition;
    }

    private function createVehicleOdometerHistory(Vehicle $vehicle, array $truck, User $admin): void
    {
        $baseline = $truck['baseline_odometer'];
        $current = $truck['current_odometer'];
        $step = max(1, intdiv($current - $baseline, 5));
        $date = now()->subDays(25);

        VehicleOdometerReading::query()->create([
            'vehicle_id' => $vehicle->id,
            'odometer' => $baseline,
            'reading_date' => $date->toDateString(),
            'source' => OdometerReadingSource::Baseline,
            'recorded_by' => $admin->id,
            'notes' => 'Initial vehicle baseline KM.',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            VehicleOdometerReading::query()->create([
                'vehicle_id' => $vehicle->id,
                'odometer' => $i === 5 ? $current : $baseline + ($step * $i),
                'reading_date' => $date->copy()->addDays($i * 5)->toDateString(),
                'source' => OdometerReadingSource::Manual,
                'recorded_by' => $admin->id,
                'notes' => "Demo odometer update {$i} of 5.",
            ]);
        }
    }

    private function createMovementHistory(Vehicle $vehicle, array $truck, array $tyresByPosition, Store $store, User $admin): void
    {
        foreach (['A', 'M', 'Q'] as $index => $position) {
            $tyre = $tyresByPosition[$position] ?? null;

            if (! $tyre) {
                continue;
            }

            TyreMovement::query()->create([
                'movement_no' => sprintf('DEMO-MOV-%s-%02d', $truck['code'], $index + 1),
                'movement_type' => $index === 0 ? MovementType::StoreToVehicle : MovementType::PositionChangeSameAsset,
                'tyre_id' => $tyre->id,
                'from_location_type' => $index === 0 ? TyreLocationType::Store : TyreLocationType::PowerVehicle,
                'from_location_id' => $index === 0 ? $store->id : $vehicle->id,
                'from_position_code' => $index === 0 ? null : $position,
                'from_odometer' => $truck['baseline_odometer'] + ($index * 250),
                'to_location_type' => TyreLocationType::PowerVehicle,
                'to_location_id' => $vehicle->id,
                'to_position_code' => $position,
                'to_odometer' => $truck['baseline_odometer'] + ($index * 250),
                'movement_date' => now()->subDays(22 - ($index * 5))->toDateString(),
                'reason' => $index === 0 ? 'Initial demo mounting' : 'Demo rotation check',
                'status' => VoucherStatus::Completed,
                'prepared_by' => $admin->id,
                'checked_by' => $admin->id,
                'approved_by' => $admin->id,
                'submitted_at' => now()->subDays(22 - ($index * 5)),
                'checked_at' => now()->subDays(21 - ($index * 5)),
                'approved_at' => now()->subDays(20 - ($index * 5)),
                'completed_at' => now()->subDays(20 - ($index * 5)),
                'notes' => 'Reset demo completed movement history.',
            ]);
        }
    }

    private function createDisposalHistory(Vehicle $vehicle, array $truck, array $brands, TyreSize $size, Store $store, User $admin): void
    {
        foreach ([1, 2] as $index) {
            $tyreCode = sprintf('%s-DISP-%02d', $truck['code'], $index);
            $tyre = Tyre::query()->create([
                'tyre_code' => $tyreCode,
                'serial_number' => "SN-{$tyreCode}",
                'brand_id' => $brands[$index % count($brands)]->id,
                'size_id' => $size->id,
                'pattern' => 'Retired Demo',
                'supplier' => 'Demo Supplier',
                'purchase_date' => now()->subYears(2)->toDateString(),
                'purchase_price' => 39000,
                'invoice_number' => "INV-{$tyreCode}",
                'initial_tread_depth' => 16.0,
                'current_tread_depth' => 2.5,
                'source' => TyreSource::PurchasedNewTyre,
                'current_location_type' => TyreLocationType::Store,
                'current_location_id' => $store->id,
                'current_position_code' => null,
                'status' => TyreStatus::Disposed,
                'notes' => 'Reset demo disposed tyre.',
            ]);

            TyreDisposal::query()->create([
                'disposal_no' => sprintf('DEMO-DISP-%s-%02d', $truck['code'], $index),
                'tyre_id' => $tyre->id,
                'last_location_type' => TyreLocationType::PowerVehicle,
                'last_location_id' => $vehicle->id,
                'last_position_code' => $index === 1 ? 'S' : 'T',
                'final_km_used' => 78000 + ($index * 3500),
                'final_condition' => $index === 1 ? 'Worn casing' : 'Sidewall damage',
                'disposal_reason' => $index === 1 ? DisposalReason::WornOut : DisposalReason::FullDamage,
                'estimated_scrap_value' => 2500 + ($index * 350),
                'sold_amount' => $index === 1 ? 2100 : null,
                'status' => VoucherStatus::Completed,
                'prepared_by' => $admin->id,
                'checked_by' => $admin->id,
                'approved_by' => $admin->id,
                'completed_at' => now()->subDays(8 - $index),
                'notes' => 'Reset demo completed disposal history.',
            ]);
        }
    }

    private function cleanupDemoData(): void
    {
        $codes = collect(self::DEMO_TRUCKS)->pluck('code')->all();
        $vehicleIds = Vehicle::query()->whereIn('vehicle_code', $codes)->pluck('id');
        $tyreIds = Tyre::query()
            ->where(function ($query) use ($codes) {
                foreach ($codes as $code) {
                    $query->orWhere('tyre_code', 'like', "{$code}-%");
                }
            })
            ->withTrashed()
            ->pluck('id');

        if ($tyreIds->isNotEmpty()) {
            TyreDisposal::query()->whereIn('tyre_id', $tyreIds)->delete();
            TyreMovement::query()->whereIn('tyre_id', $tyreIds)->delete();
            TyreBaseline::query()->whereIn('tyre_id', $tyreIds)->delete();
            TyreAssignment::query()->whereIn('tyre_id', $tyreIds)->delete();
        }

        if ($vehicleIds->isNotEmpty()) {
            VehicleOdometerReading::query()->whereIn('vehicle_id', $vehicleIds)->delete();
            VehicleCombination::query()
                ->whereIn('power_vehicle_id', $vehicleIds)
                ->orWhereIn('trailer_vehicle_id', $vehicleIds)
                ->delete();
        }

        Tyre::query()
            ->where(function ($query) use ($codes) {
                foreach ($codes as $code) {
                    $query->orWhere('tyre_code', 'like', "{$code}-%");
                }
            })
            ->withTrashed()
            ->forceDelete();

        Vehicle::query()->whereIn('vehicle_code', $codes)->withTrashed()->forceDelete();
    }
}
