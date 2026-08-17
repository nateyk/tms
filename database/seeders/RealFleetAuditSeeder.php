<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\RealFleetAuditImportService;
use Illuminate\Database\Seeder;

final class RealFleetAuditSeeder extends Seeder
{
    public function run(RealFleetAuditImportService $importer): void
    {
        $this->call(FleetOperationalDefaultsSeeder::class);

        $stats = $importer->importFromFile(database_path('data/real-fleet-audits.json'));

        $this->command?->info(sprintf(
            'Imported %d fleet combinations, %d vehicles, and %d tyres (%d empty positions).',
            $stats['fleets'],
            $stats['vehicles'],
            $stats['tyres'],
            $stats['empty_positions'],
        ));
    }
}
