<?php

namespace Tests\Feature;

use App\Enums\CombinationStatus;
use App\Enums\DisposalReason;
use App\Enums\TyreStatus;
use App\Enums\VoucherStatus;
use App\Models\Tyre;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use App\Services\ApprovalService;
use App\Services\TrailerTransferService;
use App\Services\TyreDisposalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $storeManager;

    protected User $companyManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->storeManager = User::query()->where('email', 'store@menkem.com')->firstOrFail();
        $this->companyManager = User::query()->where('email', 'manager@menkem.com')->firstOrFail();
    }

    public function test_company_manager_can_approve_submitted_trailer_transfer(): void
    {
        $trailer = Vehicle::query()->where('vehicle_code', 'TRL-045')->firstOrFail();
        $oldPower = Vehicle::query()->where('vehicle_code', 'TRK-001')->firstOrFail();
        $newPower = Vehicle::query()->where('vehicle_code', 'TRK-008')->firstOrFail();

        $transfer = app(TrailerTransferService::class)->createDraft([
            'trailer_vehicle_id' => $trailer->id,
            'from_power_vehicle_id' => $oldPower->id,
            'to_power_vehicle_id' => $newPower->id,
            'transfer_date' => now()->toDateString(),
            'reason' => 'Move trailer to another power unit.',
        ], $this->storeManager->id);

        app(ApprovalService::class)->submit($transfer);

        $this->actingAs($this->companyManager);
        app(ApprovalService::class)->approve($transfer->fresh());

        $this->assertDatabaseHas('trailer_transfers', [
            'id' => $transfer->id,
            'status' => VoucherStatus::Approved->value,
            'checked_by' => $this->companyManager->id,
            'approved_by' => $this->companyManager->id,
        ]);
    }

    public function test_company_manager_can_approve_submitted_tyre_disposal(): void
    {
        $tyre = Tyre::query()
            ->where('status', TyreStatus::Available)
            ->firstOrFail();

        $disposal = app(TyreDisposalService::class)->createDraft([
            'tyre_id' => $tyre->id,
            'disposal_reason' => DisposalReason::Scrap,
            'final_condition' => 'Scrap after inspection.',
        ], $this->storeManager->id);

        app(ApprovalService::class)->submit($disposal);

        $this->actingAs($this->companyManager);
        app(ApprovalService::class)->approve($disposal->fresh());

        $this->assertDatabaseHas('tyre_disposals', [
            'id' => $disposal->id,
            'status' => VoucherStatus::Approved->value,
            'checked_by' => $this->companyManager->id,
            'approved_by' => $this->companyManager->id,
        ]);
    }

    public function test_trailer_transfer_completion_updates_vehicle_combinations(): void
    {
        $trailer = Vehicle::query()->where('vehicle_code', 'TRL-045')->firstOrFail();
        $oldPower = Vehicle::query()->where('vehicle_code', 'TRK-001')->firstOrFail();
        $newPower = Vehicle::query()->where('vehicle_code', 'TRK-008')->firstOrFail();

        // Ensure initial combination exists
        $initialCombination = VehicleCombination::query()->firstOrCreate(
            [
                'power_vehicle_id' => $oldPower->id,
                'trailer_vehicle_id' => $trailer->id,
                'status' => CombinationStatus::Active,
            ],
            [
                'attached_date' => now()->subMonths(2)->toDateString(),
                'odometer_at_attach' => 120000,
                'attached_by' => $this->storeManager->id,
            ]
        );

        $transfer = app(TrailerTransferService::class)->createDraft([
            'trailer_vehicle_id' => $trailer->id,
            'from_power_vehicle_id' => $oldPower->id,
            'to_power_vehicle_id' => $newPower->id,
            'transfer_date' => now()->toDateString(),
            'from_odometer' => 125000,
            'to_odometer' => 98000,
            'reason' => 'Move trailer to another power unit.',
        ], $this->storeManager->id);

        app(ApprovalService::class)->submit($transfer);

        $this->actingAs($this->companyManager);
        app(ApprovalService::class)->approve($transfer->fresh());

        // Complete the transfer
        app(ApprovalService::class)->completeTrailerTransfer($transfer->fresh());

        // Verify transfer is completed
        $this->assertDatabaseHas('trailer_transfers', [
            'id' => $transfer->id,
            'status' => VoucherStatus::Completed->value,
            'approved_by' => $this->companyManager->id,
        ]);

        // Verify old combination is detached
        $this->assertDatabaseHas('vehicle_combinations', [
            'id' => $initialCombination->id,
            'power_vehicle_id' => $oldPower->id,
            'trailer_vehicle_id' => $trailer->id,
            'status' => CombinationStatus::Detached->value,
            'odometer_at_detach' => 125000,
            'detached_by' => $this->companyManager->id,
        ]);

        // Verify new combination is attached
        $this->assertDatabaseHas('vehicle_combinations', [
            'power_vehicle_id' => $newPower->id,
            'trailer_vehicle_id' => $trailer->id,
            'status' => CombinationStatus::Active->value,
            'odometer_at_attach' => 98000,
            'attached_by' => $this->companyManager->id,
            'approved_by' => $this->companyManager->id,
        ]);
    }
}
