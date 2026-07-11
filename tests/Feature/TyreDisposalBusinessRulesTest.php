<?php

namespace Tests\Feature;

use App\Enums\AssignmentAssetType;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Enums\VoucherStatus;
use App\Exceptions\TyreBusinessException;
use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreDisposal;
use App\Models\TyreMovement;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TyreAssignmentService;
use App\Services\TyreDisposalService;
use App\Services\TyreMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TyreDisposalBusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
    }

    public function test_disposal_cannot_be_created_for_already_disposed_tyre(): void
    {
        $tyre = Tyre::query()->where('status', TyreStatus::Available)->firstOrFail();

        $disposal = app(TyreDisposalService::class)->createDraft([
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
        ], $this->user->id);

        app(TyreDisposalService::class)->complete($disposal, $this->user->id);

        $tyre->refresh();
        $this->assertTrue($tyre->isDisposed());

        $this->expectException(TyreBusinessException::class);
        app(TyreDisposalService::class)->createDraft([
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
        ], $this->user->id);
    }

    public function test_disposal_completion_fails_for_already_disposed_tyre(): void
    {
        $tyre = Tyre::query()->where('status', TyreStatus::Available)->firstOrFail();

        $firstDisposal = app(TyreDisposalService::class)->createDraft([
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
        ], $this->user->id);

        app(TyreDisposalService::class)->complete($firstDisposal, $this->user->id);

        $tyre->refresh();

        // Manually create a second disposal record to test completion validation
        $secondDisposal = TyreDisposal::query()->create([
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
            'disposal_no' => 'DSP-TEST-0002',
            'status' => VoucherStatus::Approved,
            'prepared_by' => $this->user->id,
        ]);

        $this->expectException(TyreBusinessException::class);
        app(TyreDisposalService::class)->complete($secondDisposal, $this->user->id);
    }

    public function test_disposal_completion_removes_active_assignment(): void
    {
        $vehicle = Vehicle::query()->where('vehicle_code', 'TRK-001')->firstOrFail();
        $tyre = Tyre::query()->where('status', TyreStatus::Available)->firstOrFail();

        // Create an active assignment
        $assignment = app(TyreAssignmentService::class)->createActiveAssignment(
            $tyre,
            AssignmentAssetType::PowerVehicle,
            $vehicle,
            'PX1',
            1000,
            $this->user->id
        );

        $this->assertInstanceOf(TyreAssignment::class, $assignment);
        $this->assertEquals(TyreAssignmentStatus::Active, $assignment->status);

        $disposal = app(TyreDisposalService::class)->createDraft([
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
        ], $this->user->id);

        app(TyreDisposalService::class)->complete($disposal, $this->user->id);

        $assignment->refresh();
        $this->assertEquals(TyreAssignmentStatus::Removed, $assignment->status);
        $this->assertNotNull($assignment->removed_date);
        $this->assertEquals($this->user->id, $assignment->removed_by);
    }

    public function test_disposal_completion_updates_tyre_status_to_disposed(): void
    {
        $tyre = Tyre::query()->where('status', TyreStatus::Available)->firstOrFail();

        $disposal = app(TyreDisposalService::class)->createDraft([
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
        ], $this->user->id);

        app(TyreDisposalService::class)->complete($disposal, $this->user->id);

        $tyre->refresh();
        $this->assertEquals(TyreStatus::Disposed, $tyre->status);
    }

    public function test_disposal_completion_updates_tyre_location_to_disposal_yard(): void
    {
        $tyre = Tyre::query()->where('status', TyreStatus::Available)->firstOrFail();

        $disposal = app(TyreDisposalService::class)->createDraft([
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
        ], $this->user->id);

        app(TyreDisposalService::class)->complete($disposal, $this->user->id);

        $tyre->refresh();
        $this->assertEquals(TyreLocationType::DisposalYard, $tyre->current_location_type);
        $this->assertNull($tyre->current_location_id);
        $this->assertNull($tyre->current_position_code);
    }

    public function test_disposal_captures_last_location_from_store(): void
    {
        $tyre = Tyre::query()->where('status', TyreStatus::Available)->firstOrFail();
        $store = Store::query()->first();

        // Ensure tyre is in store
        $tyre->update([
            'current_location_type' => TyreLocationType::Store,
            'current_location_id' => $store->id,
            'current_position_code' => null,
        ]);

        $disposal = app(TyreDisposalService::class)->createDraft([
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
        ], $this->user->id);

        $this->assertEquals($tyre->current_location_type, $disposal->last_location_type);
        $this->assertEquals($tyre->current_location_id, $disposal->last_location_id);
        $this->assertEquals($tyre->current_position_code, $disposal->last_position_code);
    }

    public function test_disposal_captures_last_location_from_vehicle(): void
    {
        $vehicle = Vehicle::query()->where('vehicle_code', 'TRK-001')->firstOrFail();
        $tyre = Tyre::query()->where('status', TyreStatus::Available)->firstOrFail();

        // Create an active assignment
        app(TyreAssignmentService::class)->createActiveAssignment(
            $tyre,
            AssignmentAssetType::PowerVehicle,
            $vehicle,
            'PX1',
            1000,
            $this->user->id
        );

        $tyre->refresh();

        $disposal = app(TyreDisposalService::class)->createDraft([
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
        ], $this->user->id);

        $this->assertEquals($tyre->current_location_type, $disposal->last_location_type);
        $this->assertEquals($tyre->current_location_id, $disposal->last_location_id);
        $this->assertEquals($tyre->current_position_code, $disposal->last_position_code);
    }

    public function test_disposal_cannot_be_created_for_tyre_with_pending_movement(): void
    {
        $tyre = Tyre::query()->where('status', TyreStatus::Active)->firstOrFail();

        TyreMovement::query()->create([
            'movement_no' => 'MOV-TEST-0001',
            'movement_type' => 'vehicle_to_store',
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

        // Disposal creation should still work (no restriction on draft)
        $disposal = app(TyreDisposalService::class)->createDraft([
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
        ], $this->user->id);

        $this->assertInstanceOf(TyreDisposal::class, $disposal);
    }

    public function test_disposal_can_be_created_for_available_tyre(): void
    {
        $tyre = Tyre::query()->where('status', TyreStatus::Available)->firstOrFail();

        $disposal = app(TyreDisposalService::class)->createDraft([
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
        ], $this->user->id);

        $this->assertInstanceOf(TyreDisposal::class, $disposal);
        $this->assertEquals($tyre->id, $disposal->tyre_id);
    }

    public function test_disposal_can_be_created_for_active_tyre(): void
    {
        $tyre = Tyre::query()->where('status', TyreStatus::Active)->firstOrFail();

        $disposal = app(TyreDisposalService::class)->createDraft([
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
        ], $this->user->id);

        $this->assertInstanceOf(TyreDisposal::class, $disposal);
        $this->assertEquals($tyre->id, $disposal->tyre_id);
    }

    public function test_disposal_can_be_created_for_maintenance_tyre(): void
    {
        $tyre = Tyre::query()->where('status', TyreStatus::Available)->firstOrFail();
        $tyre->update(['status' => TyreStatus::Maintenance]);

        $disposal = app(TyreDisposalService::class)->createDraft([
            'tyre_id' => $tyre->id,
            'disposal_reason' => 'scrap',
        ], $this->user->id);

        $this->assertInstanceOf(TyreDisposal::class, $disposal);
        $this->assertEquals($tyre->id, $disposal->tyre_id);
    }
}
