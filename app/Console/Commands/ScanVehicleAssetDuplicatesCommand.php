<?php

namespace App\Console\Commands;

use App\Services\VehicleAssetIdentityService;
use Illuminate\Console\Command;

class ScanVehicleAssetDuplicatesCommand extends Command
{
    protected $signature = 'tms:scan-vehicle-asset-duplicates';

    protected $description = 'Report duplicate vehicle asset identity values across power vehicles, trailers, and other assets';

    public function handle(VehicleAssetIdentityService $identity): int
    {
        $report = $identity->duplicateReport();

        if ($report === []) {
            $this->info('No duplicate vehicle asset identities found.');

            return self::SUCCESS;
        }

        $this->error('Duplicate vehicle asset identities found. Review and clean these before applying strict database constraints.');

        foreach ($report as $field => $groups) {
            $this->newLine();
            $this->warn($field);

            foreach ($groups as $value => $records) {
                $this->line("Duplicate value: {$value}");
                $this->table(
                    ['ID', 'Vehicle code', 'Plate number', 'Asset type'],
                    array_map(
                        fn (array $record): array => [
                            $record['id'],
                            $record['vehicle_code'],
                            $record['plate_number'] ?? '-',
                            $record['asset_type'],
                        ],
                        $records,
                    ),
                );
            }
        }

        return self::FAILURE;
    }
}
