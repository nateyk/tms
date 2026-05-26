<?php

namespace Tests\Feature;

use App\Enums\AssignmentAssetType;
use App\Enums\AssetType;
use App\Enums\MovementType;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Enums\VoucherStatus;
use App\Exceptions\TyreBusinessException;
use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreMovement;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use App\Models\VehicleType;
use App\Services\TrailerTransferService;
use App\Services\TyreAssignmentService;
use App\Services\TyreDisposalService;
use App\Services\TyreMovementService;
use App\Services\VoucherNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TyreMovementBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
    }

    protected function movementService(): TyreMovementService
    {
        return app(TyreMovementService::class);
    }

    public function test_tyre_cannot_be_assigned_to_two_positions(): void
    {
        $vehicle = Vehicle::query()->where('vehicle_code', 'TRK-001')->firstOrFail();
        $tyre = Tyre::query()->where('status', TyreStatus::Available)->firstOrFail();

        $service = app(TyreAssignmentService::class);

        $service->createActiveAssignment(
            $tyre,
            AssignmentAssetType::PowerVehicle,
            $vehicle,
            'PX1',
            1000,
            $this->user->id
        );

        $this->expectException(TyreBusinessException::class);
        $service->createActiveAssignment(
            $tyre,
            AssignmentAssetType::PowerVehicle,
            $vehicle,
            'PX2',
            1000,
            $this->user->id
        );
    }

    public function test_vehicle_position_cannot_contain_two_active_tyres(): void
    {
        $vehicle = Vehicle::query()->where('vehicle_code', 'TRK-008')->firstOrFail();
        $tyres = Tyre::query()->where('status', TyreStatus::Available)->take(2)->get();
        $service = app(TyreAssignmentService::class);

        $service->createActiveAssignment(
            $tyres[0],
            AssignmentAssetType::PowerVehicle,
            $vehicle,
            'PY1',
            500,
            $this->user->id
        );

        $this->expectException(TyreBusinessException::class);
        $service->createActiveAssignment(
            $tyres[1],
            AssignmentAssetType::PowerVehicle,
            $vehicle,
            'PY1',
            500,
            $this->user->id
        );
    }

    public function test_vehicle_to_vehicle_movement_closes_old_assignment_and_creates_new(): void
    {
        $fromVehicle = Vehicle::query()->where('vehicle_code', 'TRK-001')->firstOrFail();
        $toVehicle = Vehicle::query()->where('vehicle_code', 'TRK-008')->firstOrFail();
        $tyre = Tyre::query()
            ->whereHas('activeAssignment', fn ($q) => $q->where('asset_id', $fromVehicle->id))
            ->firstOrFail();

        $assignment = $tyre->activeAssignment;
        $fromPosition = $assignment->position_code;

        $movement = TyreMovement::query()->create([
            'movement_no' => 'MOV-TEST-0001',
            'movement_type' => MovementType::VehicleToVehicle,
            'tyre_id' => $tyre->id,
            'from_location_type' => TyreLocationType::PowerVehicle,
            'from_location_id' => $fromVehicle->id,
            'from_position_code' => $fromPosition,
            'to_location_type' => TyreLocationType::PowerVehicle,
            'to_location_id' => $toVehicle->id,
            'to_position_code' => 'P1',
            'movement_date' => now()->toDateString(),
            'status' => VoucherStatus::Approved,
            'prepared_by' => $this->user->id,
        ]);

        $this->movementService()->complete($movement, $this->user->id);

        $assignment->refresh();
        $this->assertEquals(TyreAssignmentStatus::Removed, $assignment->status);

        $newAssignment = TyreAssignment::query()
            ->where('tyre_id', $tyre->id)
            ->where('status', TyreAssignmentStatus::Active)
            ->first();

        $this->assertNotNull($newAssignment);
        $this->assertEquals($toVehicle->id, $newAssignment->asset_id);
        $this->assertEquals('P1', $newAssignment->position_code);
    }

    public function test_destination_position_must_be_empty(): void
    {
        $toVehicle = Vehicle::query()->where('vehicle_code', 'TRK-001')->firstOrFail();
        $occupiedPosition = TyreAssignment::query()
            ->where('asset_id', $toVehicle->id)
            ->where('status', TyreAssignmentStatus::Active)
            ->value('position_code');

        $tyre = Tyre::query()->where('status', TyreStatus::Available)->firstOrFail();

        $movement = TyreMovement::query()->create([
            'movement_no' => 'MOV-TEST-0002',
            'movement_type' => MovementType::StoreToVehicle,
            'tyre_id' => $tyre->id,
            'from_location_type' => TyreLocationType::Store,
            'from_location_id' => Store::query()->first()->id,
            'to_location_type' => TyreLocationType::PowerVehicle,
            'to_location_id' => $toVehicle->id,
            'to_position_code' => $occupiedPosition,
            'movement_date' => now()->toDateString(),
            'status' => VoucherStatus::Approved,
            'prepared_by' => $this->user->id,
        ]);

        $this->expectException(TyreBusinessException::class);
        $this->movementService()->complete($movement, $this->user->id);
    }

    public function test_pending_movement_blocks_another_movement(): void
    {
        $tyre = Tyre::query()->where('status', TyreStatus::Active)->firstOrFail();

        TyreMovement::query()->create([
            'movement_no' => 'MOV-TEST-0003',
            'movement_type' => MovementType::VehicleToStore,
            'tyre_id' => $tyre->id,
            'from_location_type' => $tyre->current_location_type,
            'from_location_id' => $tyre->current_location_id,
            'from_position_code' => $tyre->current_position_code,
            'to_location_type' => TyreLocationType::Store,
            'to_location_id' => Store::query()->first()->id,
            'movement_date' => now()->toDateString(),
            'status' => VoucherStatus::Submitted,
            'prepared_by' => $this->user->id,
        ]);

        $this->expectException(TyreBusinessException::class);
        $this->movementService()->assertCanCreateMovement($tyre);
    }

    public function test_trailer_transfer_changes_combination_not_assignments(): void
    {
        $trailer = Vehicle::query()->where('vehicle_code', 'TRL-045')->firstOrFail();
        $oldPower = Vehicle::query()->where('vehicle_code', 'TRK-001')->firstOrFail();
        $newPower = Vehicle::query()->where('vehicle_code', 'TRK-008')->firstOrFail();

        $assignmentsBefore = TyreAssignment::query()
            ->where('asset_id', $trailer->id)
            ->where('status', TyreAssignmentStatus::Active)
            ->pluck('id')
            ->sort()
            ->values();

        $transfer = app(TrailerTransferService::class)->createDraft([
            'trailer_vehicle_id' => $trailer->id,
            'from_power_vehicle_id' => $oldPower->id,
            'to_power_vehicle_id' => $newPower->id,
            'transfer_date' => now()->toDateString(),
        ], $this->user->id);

        app(TrailerTransferService::class)->complete($transfer, $this->user->id);

        $assignmentsAfter = TyreAssignment::query()
            ->where('asset_id', $trailer->id)
            ->where('status', TyreAssignmentStatus::Active)
            ->pluck('id')
            ->sort()
            ->values();

        $this->assertEquals($assignmentsBefore, $assignmentsAfter);

        $this->assertTrue(
            VehicleCombination::query()
                ->where('trailer_vehicle_id', $trailer->id)
                ->where('power_vehicle_id', $newPower->id)
                ->where('status', 'active')
                ->exists()
        );
    }

    public function test_disposed_tyre_cannot_be_moved(): void
    {
        $tyre = Tyre::query()->where('status', TyreStatus::Available)->firstOrFail();

        $disposal = app(TyreDisposalService::class)->createDraft([
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
        ], $this->user->id);

        app(TyreDisposalService::class)->complete($disposal, $this->user->id);

        $tyre->refresh();
        $this->expectException(TyreBusinessException::class);
        $this->movementService()->assertCanCreateMovement($tyre);
    }

}
