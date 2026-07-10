<?php

namespace Tests\Feature;

use App\Enums\AssetType;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use App\Services\ExistingFleetTyreFitmentImporter;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Database\Seeders\TmsSampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExistingFleetTyreFitmentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_power_trailer_and_spare_tyres_for_existing_fleet_pair(): void
    {
        $this->seedFleetVehiclesOnly();

        $summary = app(ExistingFleetTyreFitmentImporter::class)->import([
            [
                'sheet' => '14766',
                'positions' => [
                    ['position' => 'A', 'brand' => 'TRIANGLE', 'serial' => 'POWER-A-001', 'fitted_km' => 1010],
                    ['position' => 'K', 'brand' => 'DUPRO', 'serial' => 'TRAILER-K-001', 'fitted_km' => 2020],
                    ['position' => 'W', 'brand' => 'TRIANGLE', 'serial' => 'SPARE-W-001', 'fitted_km' => null],
                    ['position' => 'X', 'brand' => 'TRIANGLE', 'serial' => 'NO NOBER', 'fitted_km' => null],
                ],
            ],
        ]);

        $power = $this->vehicleByPowerSuffix('14766');
        $trailer = $this->activeTrailerFor($power);

        $this->assertSame(3, $summary['imported']);
        $this->assertSame(1, $summary['skipped']);

        $this->assertTrue(TyreAssignment::query()
            ->where('asset_id', $power->id)
            ->where('position_code', 'A')
            ->where('installed_odometer', 1010)
            ->where('status', TyreAssignmentStatus::Active)
            ->whereRelation('tyre', 'serial_number', 'POWER-A-001')
            ->exists());

        $this->assertTrue(TyreAssignment::query()
            ->where('asset_id', $trailer->id)
            ->where('position_code', 'A')
            ->where('installed_odometer', 2020)
            ->where('status', TyreAssignmentStatus::Active)
            ->whereRelation('tyre', 'serial_number', 'TRAILER-K-001')
            ->exists());

        $spare = Tyre::query()->where('serial_number', 'SPARE-W-001')->firstOrFail();

        $this->assertSame(TyreSource::ExistingVehicle, $spare->source);
        $this->assertSame(TyreStatus::Available, $spare->status);
        $this->assertSame(TyreLocationType::PowerVehicle, $spare->current_location_type);
        $this->assertSame($power->id, $spare->current_location_id);
        $this->assertSame('SPARE-W', $spare->current_position_code);
        $this->assertFalse($spare->activeAssignment()->exists());
    }

    public function test_import_skips_duplicate_serials_and_occupied_positions(): void
    {
        $this->seedFleetVehiclesOnly();

        $importer = app(ExistingFleetTyreFitmentImporter::class);

        $first = $importer->import([
            [
                'sheet' => '27037',
                'positions' => [
                    ['position' => 'A', 'brand' => 'TRIANGLE', 'serial' => 'DUPLICATE-001', 'fitted_km' => 100],
                    ['position' => 'B', 'brand' => 'TRIANGLE', 'serial' => 'DUPLICATE-001', 'fitted_km' => 100],
                ],
            ],
        ]);

        $second = $importer->import([
            [
                'sheet' => '27037',
                'positions' => [
                    ['position' => 'A', 'brand' => 'TRIANGLE', 'serial' => 'DIFFERENT-001', 'fitted_km' => 200],
                ],
            ],
        ]);

        $this->assertSame(1, $first['imported']);
        $this->assertSame(1, $first['skipped']);
        $this->assertSame(0, $second['imported']);
        $this->assertSame(1, $second['skipped']);
    }

    public function test_seed_imports_fitments_for_all_existing_fleet_sheets(): void
    {
        $this->seed();

        $existingFleetPowerVehicles = Vehicle::query()
            ->where('asset_type', AssetType::PowerVehicle->value)
            ->where('vehicle_code', 'like', '%-A%')
            ->whereNotIn('vehicle_code', ['AA-024-HTK'])
            ->count();

        $this->assertSame(18, $existingFleetPowerVehicles);

        $this->assertGreaterThanOrEqual(18, Tyre::query()
            ->where('source', TyreSource::ExistingVehicle)
            ->count());

        $this->assertGreaterThanOrEqual(18, TyreAssignment::query()
            ->where('status', TyreAssignmentStatus::Active)
            ->whereRelation('tyre', 'source', TyreSource::ExistingVehicle)
            ->count());

        $this->assertFalse(Tyre::query()->where('tyre_code', 'like', 'TYR-%')->exists());
        $this->assertFalse(Tyre::query()->where('serial_number', 'like', 'SN-TYR-%')->exists());
        $this->assertFalse(Tyre::query()->where('source', TyreSource::PurchasedNewTyre)->exists());
    }

    public function test_full_seed_resets_previous_excel_import_before_reimporting(): void
    {
        $this->seed();

        $originalCount = Tyre::query()
            ->where('source', TyreSource::ExistingVehicle)
            ->count();

        Tyre::query()->where('source', TyreSource::ExistingVehicle)->firstOrFail()->update([
            'notes' => 'dirty old import record',
        ]);

        $this->seed();

        $this->assertSame($originalCount, Tyre::query()
            ->where('source', TyreSource::ExistingVehicle)
            ->count());

        $this->assertFalse(Tyre::query()
            ->where('notes', 'dirty old import record')
            ->exists());
    }

    private function vehicleByPowerSuffix(string $suffix): Vehicle
    {
        return Vehicle::query()
            ->where('asset_type', AssetType::PowerVehicle->value)
            ->where('vehicle_code', 'like', '%A'.$suffix)
            ->firstOrFail();
    }

    private function activeTrailerFor(Vehicle $power): Vehicle
    {
        $combination = VehicleCombination::query()
            ->where('power_vehicle_id', $power->id)
            ->where('status', 'active')
            ->firstOrFail();

        return Vehicle::query()->findOrFail($combination->trailer_vehicle_id);
    }

    private function seedFleetVehiclesOnly(): void
    {
        $this->seed([
            RolesAndPermissionsSeeder::class,
            SystemSettingsSeeder::class,
            TmsSampleDataSeeder::class,
        ]);
    }
}
