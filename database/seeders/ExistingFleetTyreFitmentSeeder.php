<?php

namespace Database\Seeders;

use App\Services\ExistingFleetTyreFitmentImporter;
use Illuminate\Database\Seeder;

class ExistingFleetTyreFitmentSeeder extends Seeder
{
    public function run(): void
    {
        $importer = app(ExistingFleetTyreFitmentImporter::class);
        $reset = $importer->resetImportedAndDemoTyres();
        $summary = $importer->import($this->fitments());

        if ($this->command) {
            $this->command->info(sprintf(
                'Existing fleet tyre reset: %d tyres, %d assignments removed.',
                $reset['tyres'],
                $reset['assignments'],
            ));

            $this->command->info(sprintf(
                'Existing fleet tyre fitments: %d imported (%d mounted, %d spare), %d skipped.',
                $summary['imported'],
                $summary['mounted'],
                $summary['spares'],
                $summary['skipped'],
            ));

            foreach (array_slice($summary['errors'], 0, 20) as $error) {
                $this->command->warn($error);
            }
        }
    }

    /**
     * @return list<array{sheet: string, positions: list<array{position: string, brand: string, serial: string, fitted_km: int|null}>}>
     */
    private function fitments(): array
    {
        return require database_path('seeders/data/existing_fleet_tyre_fitments.php');
    }
}
