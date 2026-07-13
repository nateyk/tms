<?php

namespace App\Console\Commands;

use App\Enums\AssetType;
use App\Enums\AssignmentAssetType;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Enums\VehicleStatus;
use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBaseline;
use App\Models\TyreBrand;
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
    protected $description = 'Seed realistic demo data for reading monitoring manual testing';

    public function handle(): int
    {
        $this->info('Seeding realistic reading monitoring demo data...');

        DB::beginTransaction();

        try {
            $admin = User::query()->where('email', 'admin@menkem.com')->first();
            if (!$admin) {
                $this->error('Admin user not found. Please run roles seeder first.');
                return 1;
            }

            // Clean up existing demo data (only for demo vehicle codes)
            $this->cleanupDemoData();

            // Create brands
            $brands = $this->createBrands();

            // Create sizes
            $sizes = $this->createSizes();

            // Create stores
            $stores = $this->createStores();

            $layoutBuilder = app(VehicleTyreLayoutBuilder::class);
            $heavyTruckLayout = $layoutBuilder->buildLayout(24, 6, 'P');

            // Create vehicle type - only Heavy Truck 24 Tyres + 2 Spares
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

            // Create TRK-024 (Heavy Truck)
            $trk024 = Vehicle::query()->updateOrCreate(
                ['vehicle_code' => 'TRK-024'],
                [
                    'plate_number' => 'AA-024-TMS',
                    'chassis_number' => 'CH-TRK-024-2026',
                    'engine_number' => 'EN-TRK-024-2026',
                    'manufacture_year' => 2024,
                    'asset_type' => AssetType::PowerVehicle,
                    'vehicle_type_id' => $heavyTruckType->id,
                    'status' => VehicleStatus::Active,
                    'odometer' => 186400,
                ]
            );

            // TRK-024 baseline configuration
            $trk024BaselineConfig = [
                'A' => [100, 100000],
                'B' => [98, 100000],
                'C' => [95, 100000],
                'D' => [96, 100000],
                'E' => [92, 95000],
                'F' => [90, 95000],
                'G' => [88, 90000],
                'H' => [87, 90000],
                'I' => [85, 90000],
                'J' => [82, 85000],
                'K' => [80, 85000],
                'L' => [78, 85000],
                'M' => null,
                'N' => null,
                'O' => null,
                'P' => null,
                'Q' => null,
                'R' => null,
                'S' => null,
                'T' => null,
                'U' => null,
                'V' => null,
                'W' => null,
                'X' => null,
            ];

            // Create and mount tyres for TRK-024
            $trk024Data = $this->createAndMountTyres(
                $trk024,
                $heavyTruckType,
                'TRK024',
                $brands,
                $sizes,
                $stores['main'],
                $admin,
                $trk024BaselineConfig,
                186400
            );

            // Create odometer readings
            $this->createOdometerReading($trk024, 186400, $admin);

            DB::commit();

            // Print summary
            $this->info("\n=== Reading Monitoring Demo Data Ready ===\n");

            $this->info("TRK-024 (Heavy Truck 24 Tyres + 2 Spares)");
            $this->info("- Odometer: 186,400 KM");
            $this->info("- Running tyres: " . $trk024Data['runningCount']);
            $this->info("- Spare tyres: " . $trk024Data['spareCount']);
            $this->info("- Baselines: " . $trk024Data['baselineCount']);
            $this->info("- Missing baselines: " . $trk024Data['missingBaselineCount']);
            $this->info("- Baseline positions: A-L");
            $this->info("- Missing positions: M-V");
            $this->info("- Spare positions: W-X");

            $this->info("\n=== Manual Testing Steps ===\n");

            $this->info("Test TRK-024:");
            $this->info("1. Open /tyres/reading-monitoring");
            $this->info("2. Select TRK-024");
            $this->info("3. Confirm A-L show baseline data");
            $this->info("4. Confirm M-V show Baseline Required");
            $this->info("5. Confirm W/X show Spare");
            $this->info("6. Update odometer from 186400 to 187000");
            $this->info("7. Confirm A-V running tyres gain 600 KM only if they have baseline");
            $this->info("8. Confirm W/X spare tyres do not gain running KM");
            $this->info("9. Use Set Missing Baselines for M-V");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error seeding demo data: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    private function createBrands(): array
    {
        $brandNames = ['Bridgestone', 'Michelin', 'Triangle', 'Double Coin', 'Aeolus'];
        $brands = [];

        foreach ($brandNames as $index => $name) {
            $code = strtoupper(substr($name, 0, 3)) . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            $brands[$name] = TyreBrand::query()->firstOrCreate(
                ['name' => $name],
                ['code' => $code, 'status' => 'active']
            );
        }

        return $brands;
    }

    private function createSizes(): array
    {
        $sizeData = [
            '315/80R22.5' => '315-80-225',
            '12R22.5' => '12R-225',
            '295/80R22.5' => '295-80-225',
        ];

        $sizes = [];
        foreach ($sizeData as $label => $code) {
            $sizes[$label] = TyreSize::query()->firstOrCreate(
                ['size_label' => $label],
                ['code' => $code, 'status' => 'active']
            );
        }

        return $sizes;
    }

    private function createStores(): array
    {
        $mainStore = Store::query()->firstOrCreate(
            ['code' => 'MAIN-STORE'],
            [
                'name' => 'Main Store',
                'address' => 'Addis Ababa, Ethiopia',
                'is_default' => true,
                'status' => 'active',
            ]
        );

        $yardStore = Store::query()->firstOrCreate(
            ['code' => 'ADDIS-YARD'],
            [
                'name' => 'Addis Ababa Yard',
                'address' => 'Addis Ababa, Ethiopia',
                'is_default' => false,
                'status' => 'active',
            ]
        );

        return ['main' => $mainStore, 'yard' => $yardStore];
    }

    private function createAndMountTyres(
        Vehicle $vehicle,
        VehicleType $vehicleType,
        string $prefix,
        array $brands,
        array $sizes,
        Store $store,
        User $admin,
        array $baselineConfig,
        int $odometer
    ): array {
        $tyres = [];
        $layout = $vehicleType->positions() ?? [];
        $positionCodes = array_column($layout, 'code');

        $runningCount = 0;
        $spareCount = 0;
        $baselineCount = 0;
        $missingBaselineCount = 0;

        foreach ($positionCodes as $index => $position) {
            $tyreCode = "{$prefix}-{$position}";
            $serialNumber = "SN-{$tyreCode}-2026";

            // Determine if this is a spare position
            $isSpare = in_array($position, ['W', 'X']);
            if ($isSpare) {
                $spareCount++;
                $price = rand(40000, 50000);
            } else {
                $runningCount++;
                $price = rand(42000, 56000);
            }

            // Select random brand and size
            $brand = $brands[array_rand($brands)];
            $size = $sizes[array_rand($sizes)];

            // Purchase date within last 6 months
            $purchaseDate = now()->subMonths(rand(1, 6))->toDateString();

            $tyre = Tyre::query()->updateOrCreate(
                ['tyre_code' => $tyreCode],
                [
                    'serial_number' => $serialNumber,
                    'brand_id' => $brand->id,
                    'size_id' => $size->id,
                    'pattern' => null,
                    'supplier' => 'Demo Supplier',
                    'purchase_date' => $purchaseDate,
                    'purchase_price' => $price,
                    'invoice_number' => "INV-{$tyreCode}",
                    'initial_tread_depth' => 16.0,
                    'current_tread_depth' => 14.5 - (rand(0, 20) / 10),
                    'source' => TyreSource::PurchasedNewTyre,
                    'current_location_type' => TyreLocationType::Store,
                    'current_location_id' => $store->id,
                    'status' => TyreStatus::Available,
                    'notes' => "Demo tyre for {$vehicle->vehicle_code}",
                ]
            );

            $assetType = $vehicle->asset_type === AssetType::Trailer
                ? AssignmentAssetType::Trailer
                : AssignmentAssetType::PowerVehicle;

            $locationType = $vehicle->asset_type === AssetType::Trailer
                ? TyreLocationType::Trailer
                : TyreLocationType::PowerVehicle;

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
                    'installed_odometer' => $odometer,
                    'km_used' => 0,
                    'installed_by' => $admin->id,
                    'notes' => "Demo assignment for {$vehicle->vehicle_code}",
                ]
            );

            $tyre->update([
                'current_location_type' => $locationType,
                'current_location_id' => $vehicle->id,
                'current_position_code' => $position,
                'status' => TyreStatus::Active,
            ]);

            // Create baseline if configured
            if (isset($baselineConfig[$position]) && $baselineConfig[$position] !== null) {
                [$percentage, $expectedLife] = $baselineConfig[$position];

                TyreBaseline::query()->updateOrCreate(
                    ['tyre_id' => $tyre->id],
                    [
                        'baseline_location_type' => $locationType->value,
                        'baseline_location_id' => $vehicle->id,
                        'baseline_position_code' => $position,
                        'baseline_odometer' => $odometer,
                        'baseline_percentage' => $percentage,
                        'expected_life_km' => $expectedLife,
                        'baseline_date' => now()->toDateString(),
                        'created_by' => $admin->id,
                        'notes' => "Demo baseline for {$vehicle->vehicle_code} position {$position}",
                    ]
                );

                $baselineCount++;
            } else {
                $missingBaselineCount++;
            }

            $tyres[] = $tyre;
        }

        return [
            'runningCount' => $runningCount,
            'spareCount' => $spareCount,
            'baselineCount' => $baselineCount,
            'missingBaselineCount' => $missingBaselineCount,
        ];
    }

    private function createOdometerReading(Vehicle $vehicle, int $odometer, User $admin): void
    {
        VehicleOdometerReading::query()->updateOrCreate(
            [
                'vehicle_id' => $vehicle->id,
                'odometer' => $odometer,
            ],
            [
                'source' => 'manual',
                'reading_date' => now()->toDateString(),
                'recorded_by' => $admin->id,
                'notes' => "Demo baseline reading for {$vehicle->vehicle_code}",
            ]
        );
    }

    private function cleanupDemoData(): void
    {
        $demoVehicleCodes = ['TRK-024', 'TRL-045', 'TRK-010'];

        // Get demo vehicle IDs
        $demoVehicleIds = Vehicle::query()->whereIn('vehicle_code', $demoVehicleCodes)->pluck('id');

        // Get demo tyre IDs
        $demoTyreIds = Tyre::query()->where(function ($query) use ($demoVehicleCodes) {
            foreach ($demoVehicleCodes as $code) {
                $query->orWhere('tyre_code', 'like', "{$code}-%");
            }
        })->pluck('id');

        // Delete demo odometer readings first (foreign key constraint)
        if ($demoVehicleIds->isNotEmpty()) {
            VehicleOdometerReading::query()->whereIn('vehicle_id', $demoVehicleIds)->delete();
        }

        // Delete demo tyre baselines
        if ($demoTyreIds->isNotEmpty()) {
            TyreBaseline::query()->whereIn('tyre_id', $demoTyreIds)->delete();
        }

        // Delete demo tyre assignments
        if ($demoVehicleIds->isNotEmpty()) {
            TyreAssignment::query()->whereIn('asset_id', $demoVehicleIds)->delete();
        }

        // Delete demo tyres (force delete to handle soft deletes)
        Tyre::query()->where(function ($query) use ($demoVehicleCodes) {
            foreach ($demoVehicleCodes as $code) {
                $query->orWhere('tyre_code', 'like', "{$code}-%");
            }
        })->forceDelete();

        // Delete demo vehicle combinations
        if ($demoVehicleIds->isNotEmpty()) {
            VehicleCombination::query()->whereIn('power_vehicle_id', $demoVehicleIds)
                ->orWhereIn('trailer_vehicle_id', $demoVehicleIds)
                ->delete();
        }

        // Delete demo vehicles (force delete to handle soft deletes)
        Vehicle::query()->whereIn('vehicle_code', $demoVehicleCodes)->forceDelete();

        // Remove unwanted vehicle types
        $typesToRemove = ['Power Unit 10 Tyres', 'Rigid Truck 6 Tyres', 'Trailer 12 Tyres'];
        VehicleType::whereIn('name', $typesToRemove)->delete();
    }
}
