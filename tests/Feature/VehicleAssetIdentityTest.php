<?php

namespace Tests\Feature;

use App\Enums\AssetType;
use App\Enums\TyreAssignmentStatus;
use App\Enums\VehicleStatus;
use App\Models\TyreAssignment;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use App\Models\VehicleType;
use App\Services\VehicleAssetIdentityService;
use App\Services\VehicleAssetImportValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VehicleAssetIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected VehicleType $powerType;

    protected VehicleType $trailerType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->powerType = VehicleType::query()
            ->where('asset_type', AssetType::PowerVehicle->value)
            ->firstOrFail();

        $this->trailerType = VehicleType::query()
            ->where('asset_type', AssetType::Trailer->value)
            ->firstOrFail();
    }

    public function test_can_create_unique_power_vehicle(): void
    {
        $vehicle = Vehicle::query()->create([
            'vehicle_code' => ' et-3-a20001 ',
            'plate_number' => ' et-3-a20001 ',
            'chassis_number' => ' ch-unique-20001 ',
            'engine_number' => ' en-unique-20001 ',
            'asset_type' => AssetType::PowerVehicle,
            'vehicle_type_id' => $this->powerType->id,
            'status' => VehicleStatus::Active,
        ]);

        $this->assertSame('ET-3-A20001', $vehicle->vehicle_code);
        $this->assertSame('CH-UNIQUE-20001', $vehicle->chassis_number);
    }

    public function test_duplicate_power_vehicle_code_is_blocked(): void
    {
        $existing = Vehicle::query()->where('asset_type', AssetType::PowerVehicle->value)->firstOrFail();

        $this->expectException(ValidationException::class);

        Vehicle::query()->create([
            'vehicle_code' => $existing->vehicle_code,
            'plate_number' => 'ET-3-NEW-POWER',
            'asset_type' => AssetType::PowerVehicle,
            'vehicle_type_id' => $this->powerType->id,
            'status' => VehicleStatus::Active,
        ]);
    }

    public function test_trailer_cannot_use_existing_power_vehicle_code(): void
    {
        $existing = Vehicle::query()->where('asset_type', AssetType::PowerVehicle->value)->firstOrFail();

        $this->expectException(ValidationException::class);

        Vehicle::query()->create([
            'vehicle_code' => $existing->vehicle_code,
            'plate_number' => 'ET-3-NEW-TRAILER',
            'asset_type' => AssetType::Trailer,
            'vehicle_type_id' => $this->trailerType->id,
            'status' => VehicleStatus::Active,
        ]);
    }

    public function test_power_vehicle_cannot_use_existing_trailer_code(): void
    {
        $existing = Vehicle::query()->where('asset_type', AssetType::Trailer->value)->firstOrFail();

        $this->expectException(ValidationException::class);

        Vehicle::query()->create([
            'vehicle_code' => $existing->vehicle_code,
            'plate_number' => 'ET-3-NEW-POWER-2',
            'asset_type' => AssetType::PowerVehicle,
            'vehicle_type_id' => $this->powerType->id,
            'status' => VehicleStatus::Active,
        ]);
    }

    public function test_update_without_changing_code_is_allowed(): void
    {
        $vehicle = Vehicle::query()->where('asset_type', AssetType::PowerVehicle->value)->firstOrFail();

        $vehicle->update(['odometer' => 222222]);

        $this->assertSame(222222, $vehicle->refresh()->odometer);
    }

    public function test_update_to_existing_code_is_blocked(): void
    {
        $vehicles = Vehicle::query()->where('asset_type', AssetType::PowerVehicle->value)->take(2)->get();

        $this->expectException(ValidationException::class);

        $vehicles[0]->update(['vehicle_code' => $vehicles[1]->vehicle_code]);
    }

    public function test_plate_and_chassis_duplicates_are_blocked_across_asset_types(): void
    {
        $power = Vehicle::query()->where('asset_type', AssetType::PowerVehicle->value)->firstOrFail();

        $this->expectException(ValidationException::class);

        Vehicle::query()->create([
            'vehicle_code' => 'TRAILER-NEW-IDENTITY',
            'plate_number' => $power->plate_number,
            'chassis_number' => $power->chassis_number,
            'asset_type' => AssetType::Trailer,
            'vehicle_type_id' => $this->trailerType->id,
            'status' => VehicleStatus::Active,
        ]);
    }

    public function test_tyre_assignment_survives_vehicle_code_change(): void
    {
        $vehicle = Vehicle::query()
            ->whereHas('activeTyreAssignments')
            ->firstOrFail();

        $assignmentId = TyreAssignment::query()
            ->where('asset_id', $vehicle->id)
            ->where('status', TyreAssignmentStatus::Active)
            ->value('id');

        $vehicle->update(['vehicle_code' => 'TRK-001-RENAMED']);

        $this->assertTrue(
            TyreAssignment::query()
                ->whereKey($assignmentId)
                ->where('asset_id', $vehicle->id)
                ->exists()
        );
    }

    public function test_import_validator_reports_file_and_database_duplicates(): void
    {
        $existing = Vehicle::query()->where('asset_type', AssetType::PowerVehicle->value)->firstOrFail();

        $errors = app(VehicleAssetImportValidator::class)->validateRows([
            [
                'row' => 2,
                'asset_type' => AssetType::PowerVehicle->value,
                'vehicle_code' => $existing->vehicle_code,
                'plate_number' => 'ET-3-IMPORT-1',
            ],
            [
                'row' => 3,
                'asset_type' => AssetType::Trailer->value,
                'vehicle_code' => 'ET-3-IMPORT-TRAILER',
                'plate_number' => 'ET-3-DUPLICATE',
            ],
            [
                'row' => 4,
                'asset_type' => AssetType::Trailer->value,
                'vehicle_code' => 'ET-3-IMPORT-TRAILER-2',
                'plate_number' => 'ET-3-DUPLICATE',
            ],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertTrue(collect($errors)->contains(fn (array $error): bool => $error['row'] === 2 && $error['field'] === 'vehicle_code'));
        $this->assertTrue(collect($errors)->contains(fn (array $error): bool => $error['row'] === 4 && $error['field'] === 'plate_number'));
    }

    public function test_duplicate_scanner_reports_dirty_existing_data(): void
    {
        DB::table('vehicles')->insert([
            'vehicle_code' => 'DIRTY-001',
            'plate_number' => 'AA-001-TRK',
            'asset_type' => AssetType::Trailer->value,
            'vehicle_type_id' => $this->trailerType->id,
            'status' => VehicleStatus::Active->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = app(VehicleAssetIdentityService::class)->duplicateReport();

        $this->assertArrayHasKey('Plate number', $report);

        $exitCode = Artisan::call('tms:scan-vehicle-asset-duplicates');

        $this->assertSame(1, $exitCode);
    }

    public function test_existing_fleet_workbook_assets_are_seeded_as_power_trailer_pairs(): void
    {
        $powerCodes = [
            'ኢት-3-A00765',
            'ኢት-3-A14761',
            'ኢት-3-A14762',
            'ኢት-3-A14763',
            'ኢት-3-A14766',
            'ኢት-3-A17806',
            'ኢት-3-A17807',
            'ኢት-3-A17808',
            'ኢት-3-A17749',
            'ኢት-3-A21632',
            'ኢት-3-A21633',
            'ኢት-3-A21634',
            'ኢት-3-A21635',
            'ኢት-3-A21636',
            'ኢት-3-A23019',
            'ኢት-3-A27036',
            'ኢት-3-A27037',
            'ኢት-3-A27049',
        ];

        $trailerCodes = [
            'ኢት-3-34969',
            'ኢት-3-34051',
            'ኢት-3-34054',
            'ኢት-3-34052',
            'ኢት-3-34055',
            'ኢት-3-34423',
            'ኢት-3-34424',
            'ኢት-3-34425',
            'ኢት-3-34422',
            'ኢት-3-36811',
            'ኢት-3-36816',
            'ኢት-3-36812',
            'ኢት-3-36814',
            'ኢት-3-36815',
            'ኢት-3-36813',
            'ኢት-3-34952',
            'ኢት-3-34951',
            'ኢት-3-35766',
        ];

        $this->assertSame(18, Vehicle::query()
            ->where('asset_type', AssetType::PowerVehicle->value)
            ->whereIn('vehicle_code', $powerCodes)
            ->count());

        $this->assertSame(18, Vehicle::query()
            ->where('asset_type', AssetType::Trailer->value)
            ->whereIn('vehicle_code', $trailerCodes)
            ->count());

        foreach ($powerCodes as $index => $powerCode) {
            $power = Vehicle::query()->where('vehicle_code', $powerCode)->firstOrFail();
            $trailer = Vehicle::query()->where('vehicle_code', $trailerCodes[$index])->firstOrFail();

            $this->assertTrue(VehicleCombination::query()
                ->where('power_vehicle_id', $power->id)
                ->where('trailer_vehicle_id', $trailer->id)
                ->where('status', 'active')
                ->exists());
        }
    }
}
