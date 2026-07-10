<?php

namespace App\Console\Commands;

use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBaseline;
use App\Models\TyreInspection;
use App\Models\TyreBrand;
use App\Models\TyreSize;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Enums\AssetType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedDemoAuditData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tms:seed-demo-audit-data 
                            {--force : Force execution without confirmation}
                            {--preview : Preview what will be created without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed TMS demo audit data based on tyre audit screenshots';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $preview = $this->option('preview');
        $force = $this->option('force');

        $this->info('=== TMS Demo Audit Data Seeder ===');
        $this->newLine();

        // Check if environment is production
        if (app()->environment('production') && !$force) {
            $this->error('Cannot run demo data seeder in production environment without --force flag.');
            return self::FAILURE;
        }

        // Get or create vehicle type
        $vehicleType = $this->getHeavyTruckVehicleType();
        
        if (!$vehicleType) {
            $this->error('Heavy Truck vehicle type not found. Please run tms:reset-demo-data first.');
            return self::FAILURE;
        }

        $this->info("Using Vehicle Type: {$vehicleType->name}");
        $this->newLine();

        // Define demo audit sets
        $demoSets = $this->getDemoAuditSets($vehicleType->id);

        $this->info('Demo Audit Sets to Create:');
        $this->line(str_repeat('-', 70));
        foreach ($demoSets as $index => $set) {
            $this->line(sprintf(
                "%d. %s | %s | KM: %s | Date: %s",
                $index + 1,
                $set['vehicle_code'],
                $set['plate_number'],
                number_format($set['odometer']),
                $set['audit_date']
            ));
        }
        $this->line(str_repeat('-', 70));
        $this->newLine();

        if ($preview) {
            $this->warn('PREVIEW MODE - No changes will be made');
            $this->newLine();
            $this->info('Summary of what will be created:');
            $this->line('- 2 Vehicles (Power Units)');
            $this->line('- 48 Tyres (24 per vehicle)');
            $this->line('- 48 Tyre Assignments');
            $this->line('- 48 Tyre Baselines');
            $this->line('- 48 Tyre Inspections');
            return self::SUCCESS;
        }

        // Confirm execution
        if (!$force && !$this->confirm('Do you want to proceed with seeding demo audit data?')) {
            $this->warn('Operation cancelled.');
            return self::FAILURE;
        }

        $this->info('Starting demo audit data seeding...');
        $this->newLine();

        try {
            DB::transaction(function () use ($demoSets) {
                foreach ($demoSets as $set) {
                    $this->createDemoAuditSet($set);
                }
            });

            $this->newLine();
            $this->info('✓ Demo audit data seeded successfully!');
            $this->newLine();
            $this->showFinalState();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Error during demo data seeding: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function getHeavyTruckVehicleType(): ?VehicleType
    {
        return VehicleType::where('name', 'Heavy Truck 24 Tyres + 2 Spares')->first();
    }

    private function getDemoAuditSets(int $vehicleTypeId): array
    {
        return [
            [
                'vehicle_code' => 'VH-A14761',
                'plate_number' => 'ET-3-A14761',
                'trailer_plate' => 'ET-3-34051',
                'odometer' => 171742,
                'audit_date' => '2026-07-08',
                'vehicle_type_id' => $vehicleTypeId,
                'tyres' => $this->getDemoSet1Tyres(),
            ],
            [
                'vehicle_code' => 'VH-A14766',
                'plate_number' => 'ET-3-A14766',
                'trailer_plate' => 'ET-3-34055',
                'odometer' => 170416,
                'audit_date' => '2026-07-07',
                'vehicle_type_id' => $vehicleTypeId,
                'tyres' => $this->getDemoSet2Tyres(),
            ],
        ];
    }

    private function getDemoSet1Tyres(): array
    {
        return [
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
        ];
    }

    private function getDemoSet2Tyres(): array
    {
        return [
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
        ];
    }

    private function createDemoAuditSet(array $set): void
    {
        $this->info("Processing: {$set['vehicle_code']} ({$set['plate_number']})");

        // Create vehicle
        $vehicle = Vehicle::create([
            'vehicle_code' => $set['vehicle_code'],
            'plate_number' => $set['plate_number'],
            'asset_type' => AssetType::PowerVehicle,
            'vehicle_type_id' => $set['vehicle_type_id'],
            'status' => 'active',
            'odometer' => $set['odometer'],
            'odometer_last_updated_at' => now(),
            'odometer_last_updated_by' => 1, // Use admin user
        ]);

        $this->line("  ✓ Created vehicle: {$vehicle->vehicle_code}");

        // Get or create tyre brands
        $brands = [];
        foreach ($set['tyres'] as $tyreData) {
            if ($tyreData['brand'] && !isset($brands[$tyreData['brand']])) {
                $brand = TyreBrand::firstOrCreate(
                    ['name' => $tyreData['brand']],
                    ['description' => 'Demo brand from audit data']
                );
                $brands[$tyreData['brand']] = $brand->id;
            }
        }

        // Get or create a default tyre size
        $sizeId = TyreSize::firstOrCreate(
            ['size_label' => '295/80R22.5'],
            ['description' => 'Standard truck tyre size']
        )->id;

        // Create tyres and assignments
        foreach ($set['tyres'] as $tyreData) {
            if ($tyreData['serial']) {
                // Create tyre with actual data
                $tyreCode = $this->generateTyreCode($tyreData['serial']);
                
                $tyre = Tyre::firstOrCreate(
                    ['serial_number' => $tyreData['serial']],
                    [
                        'tyre_code' => $tyreCode,
                        'brand_id' => $brands[$tyreData['brand']] ?? null,
                        'size_id' => $sizeId,
                        'pattern' => 'Demo Pattern',
                        'source' => 'purchased_new_tyre',
                        'current_location_type' => 'power_vehicle',
                        'current_location_id' => $vehicle->id,
                        'current_position_code' => $tyreData['position'],
                        'status' => 'active',
                        'initial_tread_depth' => 20.00, // Standard new tread depth
                    ]
                );

                // Create tyre assignment
                TyreAssignment::firstOrCreate(
                    [
                        'tyre_id' => $tyre->id,
                        'asset_type' => 'power_vehicle',
                        'asset_id' => $vehicle->id,
                        'position_code' => $tyreData['position'],
                        'status' => 'active',
                    ],
                    [
                        'installed_date' => $set['audit_date'],
                        'installed_odometer' => $set['odometer'] - 50000, // Assume fitted 50k KM ago
                        'installed_by' => 1,
                    ]
                );

                // Create baseline
                if (!TyreBaseline::where('tyre_id', $tyre->id)->exists()) {
                    TyreBaseline::create([
                        'tyre_id' => $tyre->id,
                        'baseline_location_type' => 'power_vehicle',
                        'baseline_location_id' => $vehicle->id,
                        'baseline_position_code' => $tyreData['position'],
                        'baseline_odometer' => $set['odometer'] - 50000,
                        'baseline_percentage' => 100.00,
                        'expected_life_km' => 100000,
                        'baseline_date' => $set['audit_date'],
                        'created_by' => 1,
                    ]);
                }

                // Create inspection with tread depth and pressure
                $treadDepth = $this->percentageToTreadDepth($tyreData['percentage']);
                $pressure = $this->percentageToPressure($tyreData['percentage']);

                TyreInspection::create([
                    'tyre_id' => $tyre->id,
                    'inspection_date' => $set['audit_date'],
                    'tread_depth' => $treadDepth,
                    'pressure' => $pressure,
                    'condition' => $this->getConditionFromPercentage($tyreData['percentage']),
                    'inspector' => 'Demo Auditor',
                    'inspected_by' => 1,
                    'notes' => $tyreData['remark'],
                ]);

                $this->line("  ✓ Created tyre: {$tyre->position_code} | {$tyre->serial_number} | {$tyreData['percentage']}%");
            } elseif (in_array($tyreData['remark'], ['NOT FOUND', 'NO NUMBER'])) {
                // Create missing tyre placeholder
                $tyreCode = 'MISSING-' . $tyreData['position'];
                
                $tyre = Tyre::firstOrCreate(
                    ['tyre_code' => $tyreCode],
                    [
                        'serial_number' => 'MISSING-' . $tyreData['position'] . '-' . $vehicle->vehicle_code,
                        'brand_id' => null,
                        'size_id' => $sizeId,
                        'pattern' => null,
                        'source' => 'purchased_new_tyre',
                        'current_location_type' => 'power_vehicle',
                        'current_location_id' => $vehicle->id,
                        'current_position_code' => $tyreData['position'],
                        'status' => 'pending_approval',
                        'initial_tread_depth' => null,
                    ]
                );

                TyreAssignment::firstOrCreate(
                    [
                        'tyre_id' => $tyre->id,
                        'asset_type' => 'power_vehicle',
                        'asset_id' => $vehicle->id,
                        'position_code' => $tyreData['position'],
                        'status' => 'active',
                    ],
                    [
                        'installed_date' => $set['audit_date'],
                        'installed_odometer' => $set['odometer'],
                        'installed_by' => 1,
                    ]
                );

                TyreInspection::create([
                    'tyre_id' => $tyre->id,
                    'inspection_date' => $set['audit_date'],
                    'tread_depth' => null,
                    'pressure' => null,
                    'condition' => 'MISSING',
                    'inspector' => 'Demo Auditor',
                    'inspected_by' => 1,
                    'notes' => $tyreData['remark'],
                ]);

                $this->line("  ✓ Created missing tyre: {$tyre->position_code} | {$tyreData['remark']}");
            }
            // Skip SPARE positions for now
        }

        $this->line("  ✓ Completed vehicle: {$vehicle->vehicle_code}");
        $this->newLine();
    }

    private function generateTyreCode(string $serial): string
    {
        return 'TYR-' . substr($serial, -6);
    }

    private function percentageToTreadDepth(?int $percentage): ?float
    {
        if ($percentage === null) {
            return null;
        }

        // Map percentage to tread depth (assuming 20mm = 100%)
        $mapping = [
            95 => 18.0,
            90 => 17.0,
            60 => 12.0,
            55 => 11.0,
            50 => 10.0,
            45 => 9.0,
            40 => 8.0,
            35 => 7.0,
            30 => 6.0,
            25 => 5.0,
        ];

        return $mapping[$percentage] ?? round($percentage * 0.2, 1);
    }

    private function percentageToPressure(?int $percentage): ?float
    {
        if ($percentage === null) {
            return null;
        }

        // Map percentage to pressure
        if ($percentage >= 80) {
            return rand(105, 115); // Healthy
        } elseif ($percentage >= 50) {
            return rand(95, 104); // Normal
        } elseif ($percentage >= 30) {
            return rand(95, 104); // Warning
        } else {
            return rand(80, 94); // Critical
        }
    }

    private function getConditionFromPercentage(?int $percentage): string
    {
        if ($percentage === null) {
            return 'MISSING';
        }

        if ($percentage >= 80) {
            return 'HEALTHY';
        } elseif ($percentage >= 50) {
            return 'NORMAL';
        } elseif ($percentage >= 30) {
            return 'WARNING';
        } else {
            return 'CRITICAL';
        }
    }

    private function showFinalState(): void
    {
        $this->info('Final State:');
        $this->line(str_repeat('-', 70));

        $tables = [
            'vehicle_types',
            'vehicles',
            'tyres',
            'tyre_assignments',
            'tyre_baselines',
            'tyre_inspections',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $this->line(sprintf('%-35s %8d', $table, $count));
            }
        }

        $this->line(str_repeat('-', 70));
        $this->newLine();

        $this->info('Vehicles Created:');
        $this->line(str_repeat('-', 70));
        $vehicles = Vehicle::with('vehicleType')->get();
        foreach ($vehicles as $vehicle) {
            $tyreCount = DB::table('tyres')
                ->where('current_location_id', $vehicle->id)
                ->count();
            $this->line(sprintf(
                "%s | %s | %s | Tyres: %d",
                $vehicle->vehicle_code,
                $vehicle->plate_number,
                $vehicle->vehicleType->name,
                $tyreCount
            ));
        }
        $this->line(str_repeat('-', 70));
        $this->newLine();
        $this->info('Demo audit data ready for testing Reading Monitoring UI.');
    }
}
