<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetDemoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tms:reset-demo-data 
                            {--force : Force execution without confirmation}
                            {--preview : Preview what would be deleted without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset TMS demo data safely - removes fleet/tyre test data while keeping users, roles, and settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $preview = $this->option('preview');
        $force = $this->option('force');

        $this->info('=== TMS Demo Data Reset ===');
        $this->newLine();

        // Show current state
        $this->showCurrentState();

        if ($preview) {
            $this->warn('PREVIEW MODE - No changes will be made');
            $this->newLine();
            return self::SUCCESS;
        }

        // Confirm execution
        if (!$force && !$this->confirm('Do you want to proceed with the demo data reset? This will delete all fleet and tyre test data.')) {
            $this->warn('Operation cancelled.');
            return self::FAILURE;
        }

        $this->info('Starting demo data reset...');
        $this->newLine();

        try {
            DB::transaction(function () use ($preview) {
                $this->deleteActivityLogs();
                $this->deleteTyreRelatedData();
                $this->deleteVehicleRelatedData();
                $this->deleteUnwantedVehicleTypes();
                $this->ensureHeavyTruckVehicleTypeExists();
            });

            $this->newLine();
            $this->info('✓ Demo data reset completed successfully!');
            $this->newLine();
            $this->showFinalState();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Error during demo data reset: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function showCurrentState(): void
    {
        $this->info('Current State:');
        $this->line(str_repeat('-', 60));

        $tables = [
            'vehicle_types',
            'vehicles',
            'vehicle_combinations',
            'trailer_transfers',
            'tyres',
            'tyre_movements',
            'tyre_assignments',
            'tyre_maintenance',
            'tyre_disposals',
            'tyre_inspections',
            'tyre_baselines',
            'vehicle_odometer_readings',
            'approval_requests',
            'approval_steps',
            'activity_log',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $this->line(sprintf('%-35s %8d', $table, $count));
            }
        }

        $this->line(str_repeat('-', 60));
        $this->newLine();

        $this->info('Current Vehicle Types:');
        $this->line(str_repeat('-', 60));
        $vehicleTypes = DB::table('vehicle_types')
            ->select('id', 'name', 'asset_type', 'axle_count', 'tyre_count', 'status')
            ->get();
        
        foreach ($vehicleTypes as $vt) {
            $keep = $vt->name === 'Heavy Truck 24 Tyres + 2 Spares' ? 'KEEP' : 'DELETE';
            $this->line(sprintf(
                "ID: %-3d | %-30s | %-15s | Axles: %-2d | Tyres: %-3d | Status: %s [%s]",
                $vt->id,
                $vt->name,
                $vt->asset_type,
                $vt->axle_count,
                $vt->tyre_count,
                $vt->status,
                $keep
            ));
        }
        $this->line(str_repeat('-', 60));
        $this->newLine();
    }

    private function deleteActivityLogs(): void
    {
        $this->info('Deleting activity logs related to vehicles and tyres...');
        
        $deleted = DB::table('activity_log')
            ->where(function ($query) {
                $query->where('subject_type', 'like', '%Vehicle%')
                    ->orWhere('subject_type', 'like', '%Tyre%')
                    ->orWhere('subject_type', 'like', '%Trailer%')
                    ->orWhere('subject_type', 'like', '%Movement%')
                    ->orWhere('subject_type', 'like', '%Disposal%')
                    ->orWhere('subject_type', 'like', '%Assignment%')
                    ->orWhere('subject_type', 'like', '%Inspection%')
                    ->orWhere('subject_type', 'like', '%Baseline%');
            })
            ->delete();

        $this->line("  ✓ Deleted {$deleted} activity log entries");
    }

    private function deleteTyreRelatedData(): void
    {
        $this->info('Deleting tyre-related data...');

        // Delete in correct order (child before parent)
        $tables = [
            'tyre_baselines',
            'tyre_inspections',
            'tyre_maintenance',
            'tyre_disposals',
            'tyre_movements',
            'tyre_assignments',
            'tyres',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                if ($count > 0) {
                    DB::table($table)->delete();
                    $this->line("  ✓ Deleted all records from {$table} ({$count} records)");
                }
            }
        }
    }

    private function deleteVehicleRelatedData(): void
    {
        $this->info('Deleting vehicle-related data...');

        // Delete in correct order (child before parent)
        $tables = [
            'vehicle_odometer_readings',
            'vehicle_combinations',
            'trailer_transfers',
            'vehicles',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                if ($count > 0) {
                    DB::table($table)->delete();
                    $this->line("  ✓ Deleted all records from {$table} ({$count} records)");
                }
            }
        }
    }

    private function deleteUnwantedVehicleTypes(): void
    {
        $this->info('Deleting unwanted vehicle types...');

        $deleted = DB::table('vehicle_types')
            ->where('name', '!=', 'Heavy Truck 24 Tyres + 2 Spares')
            ->delete();

        $this->line("  ✓ Deleted {$deleted} vehicle type(s)");
    }

    private function ensureHeavyTruckVehicleTypeExists(): void
    {
        $this->info('Ensuring Heavy Truck vehicle type exists...');

        $heavyTruck = DB::table('vehicle_types')
            ->where('name', 'Heavy Truck 24 Tyres + 2 Spares')
            ->first();

        if ($heavyTruck) {
            $this->line("  ✓ Heavy Truck vehicle type already exists (ID: {$heavyTruck->id})");
            
            // Update to ensure correct values
            DB::table('vehicle_types')
                ->where('id', $heavyTruck->id)
                ->update([
                    'asset_type' => 'power_vehicle',
                    'axle_count' => 6,
                    'tyre_count' => 24,
                    'status' => 'active',
                ]);
            
            $this->line("  ✓ Updated Heavy Truck vehicle type configuration");
        } else {
            $id = DB::table('vehicle_types')->insertGetId([
                'name' => 'Heavy Truck 24 Tyres + 2 Spares',
                'asset_type' => 'power_vehicle',
                'axle_count' => 6,
                'tyre_count' => 24,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->line("  ✓ Created Heavy Truck vehicle type (ID: {$id})");
        }
    }

    private function showFinalState(): void
    {
        $this->info('Final State:');
        $this->line(str_repeat('-', 60));

        $vehicleTypes = DB::table('vehicle_types')
            ->select('id', 'name', 'asset_type', 'axle_count', 'tyre_count', 'status')
            ->get();

        foreach ($vehicleTypes as $vt) {
            $this->line(sprintf(
                "ID: %-3d | %-30s | %-15s | Axles: %-2d | Tyres: %-3d | Status: %s",
                $vt->id,
                $vt->name,
                $vt->asset_type,
                $vt->axle_count,
                $vt->tyre_count,
                $vt->status
            ));
        }

        $this->line(str_repeat('-', 60));
        $this->newLine();

        $this->info('Remaining record counts:');
        $this->line(str_repeat('-', 60));

        $tables = [
            'vehicle_types',
            'vehicles',
            'vehicle_combinations',
            'trailer_transfers',
            'tyres',
            'tyre_movements',
            'tyre_assignments',
            'tyre_maintenance',
            'tyre_disposals',
            'tyre_inspections',
            'tyre_baselines',
            'vehicle_odometer_readings',
            'approval_requests',
            'approval_steps',
            'activity_log',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $this->line(sprintf('%-35s %8d', $table, $count));
            }
        }

        $this->line(str_repeat('-', 60));
        $this->newLine();
        $this->info('System is now ready for fresh sample data.');
    }
}
