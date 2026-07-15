<?php

namespace App\Console\Commands;

use Database\Seeders\FleetOperationalDefaultsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanOperationalDataCommand extends Command
{
    protected $signature = 'tms:clean-operational-data
        {--force : Run without confirmation}
        {--seed-defaults : Seed production starter setup data after cleanup}';

    protected $description = 'Delete fleet, tyre, voucher, and demo operating data while keeping users, roles, permissions, and settings';

    /** @var list<string> */
    private array $tables = [
        'approval_steps',
        'approval_requests',
        'tyre_inspections',
        'tyre_disposals',
        'tyre_maintenance',
        'tyre_assignments',
        'tyre_movements',
        'tyre_baselines',
        'vehicle_odometer_readings',
        'trailer_transfers',
        'vehicle_combinations',
        'tyres',
        'vehicles',
        'vehicle_types',
        'locations',
        'stores',
        'tyre_sizes',
        'tyre_brands',
        'activity_log',
        'media',
    ];

    public function handle(): int
    {
        $tables = collect($this->tables)
            ->filter(fn (string $table) => Schema::hasTable($table))
            ->values();

        if ($tables->isEmpty()) {
            $this->warn('No operational tables found to clean.');

            return self::SUCCESS;
        }

        $this->warn('This will delete operational fleet/tyre/demo data.');
        $this->line('Kept: users, roles, permissions, role assignments, system settings, migrations, cache/jobs.');
        $this->line('Deleted tables: '.$tables->implode(', '));

        if (! $this->option('force') && ! $this->confirm('Continue with operational data cleanup?')) {
            $this->info('Cleanup cancelled.');

            return self::FAILURE;
        }

        $this->disableForeignKeyChecks();

        try {
            foreach ($tables as $table) {
                DB::table($table)->delete();
                $this->resetAutoIncrement($table);
                $this->line("Cleaned {$table}");
            }
        } finally {
            $this->enableForeignKeyChecks();
        }

        $this->info('Operational data cleaned. Users, roles, permissions, and settings were preserved.');

        if ($this->option('seed-defaults')) {
            $this->call('db:seed', [
                '--class' => FleetOperationalDefaultsSeeder::class,
                '--force' => true,
            ]);
            $this->info('Starter fleet defaults seeded.');
        }

        return self::SUCCESS;
    }

    private function disableForeignKeyChecks(): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS=0'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = OFF'),
            'pgsql' => DB::statement('SET CONSTRAINTS ALL DEFERRED'),
            default => null,
        };
    }

    private function enableForeignKeyChecks(): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement('SET FOREIGN_KEY_CHECKS=1'),
            'sqlite' => DB::statement('PRAGMA foreign_keys = ON'),
            default => null,
        };
    }

    private function resetAutoIncrement(string $table): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1"),
            'sqlite' => Schema::hasTable('sqlite_sequence')
                ? DB::table('sqlite_sequence')->where('name', $table)->delete()
                : null,
            'pgsql' => $this->resetPostgresSequence($table),
            default => null,
        };
    }

    private function resetPostgresSequence(string $table): void
    {
        DB::statement("ALTER SEQUENCE IF EXISTS {$table}_id_seq RESTART WITH 1");
    }
}
