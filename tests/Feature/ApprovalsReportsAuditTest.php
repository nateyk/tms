<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreMovement;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalsReportsAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $userWithoutPermissions;
    protected User $preparer;
    protected User $checker;
    protected User $approver;
    private static int $tyreSequence = 11000;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->adminUser = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
        $this->preparer = User::factory()->create(['email' => 'preparer@test.com']);
        $this->preparer->assignRole('Store Keeper');
        $this->checker = User::factory()->create(['email' => 'checker@test.com']);
        $this->checker->assignRole('Store Manager');
        $this->approver = User::factory()->create(['email' => 'approver@test.com']);
        $this->approver->assignRole('Company Manager');
        
        // Create a user without report.view or audit.view permissions
        $this->userWithoutPermissions = User::factory()->create([
            'email' => 'nopermissions@test.com',
        ]);
        $this->userWithoutPermissions->syncRoles([]);
    }

    public function test_pending_approvals_page_loads_for_authorized_user()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('approvals.pending'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            return $page->component('approvals/pending');
        });
    }

    public function test_pending_approvals_contains_movement_data()
    {
        // Create a submitted movement
        $tyre = $this->createAvailableTyre();
        $store = Store::query()->firstOrFail();

        $movement = TyreMovement::query()->create([
            'movement_no' => 'TEST-001',
            'movement_type' => 'store_to_vehicle',
            'tyre_id' => $tyre->id,
            'from_location_type' => 'store',
            'from_location_id' => $store->id,
            'from_position_code' => null,
            'from_odometer' => null,
            'to_location_type' => 'store',
            'to_location_id' => $store->id,
            'to_position_code' => 'A',
            'to_odometer' => null,
            'movement_date' => now(),
            'reason' => 'Test movement',
            'status' => 'submitted',
            'prepared_by' => $this->preparer->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->checker)
            ->get(route('approvals.pending'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            return $page->component('approvals/pending')
                ->has('movements')
                ->where('movements.0.movement_type_label', 'Store to Vehicle')
                ->has('transfers')
                ->has('disposals');
        });
    }

    public function test_reports_page_loads_for_authorized_user()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            return $page->component('reports/index')
                ->has('tyreStock')
                ->has('tyreLifecycle')
                ->has('tyreKmPerformance')
                ->has('movements')
                ->has('filters');
        });
    }

    public function test_reports_page_denied_for_unauthorized_user()
    {
        $response = $this->actingAs($this->userWithoutPermissions)
            ->get(route('reports.index'));

        $response->assertStatus(403);
    }

    public function test_audit_logs_page_loads_for_authorized_user()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('audit-logs.index'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            return $page->component('audit-logs/index')
                ->has('logs')
                ->has('filters');
        });
    }

    public function test_audit_logs_page_denied_for_unauthorized_user()
    {
        $response = $this->actingAs($this->userWithoutPermissions)
            ->get(route('audit-logs.index'));

        $response->assertStatus(403);
    }

    public function test_submitted_movement_can_be_checked()
    {
        $tyre = $this->createAvailableTyre();
        $store = Store::query()->firstOrFail();
        
        $movement = TyreMovement::query()->create([
            'movement_no' => 'TEST-002',
            'movement_type' => 'store_to_vehicle',
            'tyre_id' => $tyre->id,
            'from_location_type' => 'store',
            'from_location_id' => $store->id,
            'from_position_code' => null,
            'from_odometer' => null,
            'to_location_type' => 'store',
            'to_location_id' => $store->id,
            'to_position_code' => 'A',
            'to_odometer' => null,
            'movement_date' => now(),
            'reason' => 'Test movement',
            'status' => 'submitted',
            'prepared_by' => $this->preparer->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->checker)
            ->post(route('tyres.movements.check', $movement->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('tyre_movements', [
            'id' => $movement->id,
            'status' => 'checked',
        ]);
    }

    public function test_checked_movement_can_be_approved()
    {
        $tyre = $this->createAvailableTyre();
        $store = Store::query()->firstOrFail();
        
        $movement = TyreMovement::query()->create([
            'movement_no' => 'TEST-003',
            'movement_type' => 'store_to_vehicle',
            'tyre_id' => $tyre->id,
            'from_location_type' => 'store',
            'from_location_id' => $store->id,
            'from_position_code' => null,
            'from_odometer' => null,
            'to_location_type' => 'store',
            'to_location_id' => $store->id,
            'to_position_code' => 'A',
            'to_odometer' => null,
            'movement_date' => now(),
            'reason' => 'Test movement',
            'status' => 'checked',
            'prepared_by' => $this->preparer->id,
            'checked_by' => $this->checker->id,
            'checked_at' => now(),
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->approver)
            ->post(route('tyres.movements.approve', $movement->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('tyre_movements', [
            'id' => $movement->id,
            'status' => 'approved',
        ]);
    }

    public function test_checked_movement_can_be_rejected()
    {
        $tyre = $this->createAvailableTyre();
        $store = Store::query()->firstOrFail();
        
        $movement = TyreMovement::query()->create([
            'movement_no' => 'TEST-004',
            'movement_type' => 'store_to_vehicle',
            'tyre_id' => $tyre->id,
            'from_location_type' => 'store',
            'from_location_id' => $store->id,
            'from_position_code' => null,
            'from_odometer' => null,
            'to_location_type' => 'store',
            'to_location_id' => $store->id,
            'to_position_code' => 'A',
            'to_odometer' => null,
            'movement_date' => now(),
            'reason' => 'Test movement',
            'status' => 'checked',
            'prepared_by' => $this->preparer->id,
            'checked_by' => $this->checker->id,
            'checked_at' => now(),
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->approver)
            ->post(route('tyres.movements.reject', $movement->id), [
                'reason' => 'Test rejection',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tyre_movements', [
            'id' => $movement->id,
            'status' => 'rejected',
        ]);
    }

    public function test_draft_movement_cannot_be_hard_deleted()
    {
        $tyre = $this->createAvailableTyre();
        $store = Store::query()->firstOrFail();

        $movement = TyreMovement::query()->create([
            'movement_no' => 'TEST-NO-DELETE',
            'movement_type' => 'store_to_vehicle',
            'tyre_id' => $tyre->id,
            'from_location_type' => 'store',
            'from_location_id' => $store->id,
            'from_position_code' => null,
            'from_odometer' => null,
            'to_location_type' => 'store',
            'to_location_id' => $store->id,
            'to_position_code' => 'A',
            'to_odometer' => null,
            'movement_date' => now(),
            'reason' => 'Test movement',
            'status' => 'draft',
            'prepared_by' => $this->preparer->id,
        ]);

        $this->actingAs($this->adminUser)
            ->delete(route('tyres.movements.destroy', $movement->id))
            ->assertForbidden();

        $this->assertDatabaseHas('tyre_movements', [
            'id' => $movement->id,
            'status' => 'draft',
        ]);
    }

    public function test_approved_movement_can_be_voided_before_completion()
    {
        $tyre = $this->createAvailableTyre();
        $store = Store::query()->firstOrFail();

        $movement = TyreMovement::query()->create([
            'movement_no' => 'TEST-VOID-APPROVED',
            'movement_type' => 'store_to_vehicle',
            'tyre_id' => $tyre->id,
            'from_location_type' => 'store',
            'from_location_id' => $store->id,
            'from_position_code' => null,
            'from_odometer' => null,
            'to_location_type' => 'store',
            'to_location_id' => $store->id,
            'to_position_code' => 'A',
            'to_odometer' => null,
            'movement_date' => now(),
            'reason' => 'Test movement',
            'status' => 'approved',
            'prepared_by' => $this->preparer->id,
            'checked_by' => $this->checker->id,
            'approved_by' => $this->approver->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($this->approver)
            ->post(route('tyres.movements.cancel', $movement->id), ['reason' => 'Route changed before loading'])
            ->assertRedirect(route('tyres.movements.index'));

        $this->assertDatabaseHas('tyre_movements', [
            'id' => $movement->id,
            'status' => 'cancelled',
            'voided_by' => $this->approver->id,
            'void_reason' => 'Route changed before loading',
        ]);
    }

    public function test_preparer_cannot_check_their_own_movement(): void
    {
        $movement = $this->createSubmittedMovement($this->preparer->id);

        $this->actingAs($this->preparer)
            ->post(route('tyres.movements.check', $movement))
            ->assertForbidden();
    }

    public function test_checker_cannot_approve_their_own_movement(): void
    {
        $dualReviewer = User::factory()->create(['email' => 'dual-reviewer@test.com']);
        $dualReviewer->assignRole('Technic and Maintenance Head');
        $movement = $this->createSubmittedMovement($this->preparer->id, $dualReviewer->id, 'checked');

        $this->actingAs($dualReviewer)
            ->post(route('tyres.movements.approve', $movement))
            ->assertForbidden();
    }

    public function test_movement_cannot_skip_checking_before_approval(): void
    {
        $movement = $this->createSubmittedMovement($this->preparer->id);

        $this->actingAs($this->approver)
            ->post(route('tyres.movements.approve', $movement))
            ->assertForbidden();
    }

    public function test_submitted_movement_cannot_be_edited_even_by_a_super_admin(): void
    {
        $movement = $this->createSubmittedMovement($this->preparer->id);

        $this->actingAs($this->adminUser)
            ->put(route('tyres.movements.update', $movement), [
                'movement_date' => now()->toDateString(),
                'to_location_type' => 'store',
                'to_location_id' => Store::query()->firstOrFail()->id,
                'reason' => 'Attempted override',
            ])
            ->assertSessionHas('error', 'Only draft movement vouchers can be edited.');

        $this->assertDatabaseHas('tyre_movements', [
            'id' => $movement->id,
            'reason' => 'Test movement',
            'status' => 'submitted',
        ]);
    }

    public function test_completed_movement_cannot_be_voided(): void
    {
        $movement = $this->createSubmittedMovement($this->preparer->id, $this->checker->id, 'completed');

        $this->actingAs($this->adminUser)
            ->post(route('tyres.movements.cancel', $movement), ['reason' => 'Attempted void'])
            ->assertSessionHas('error', 'Cannot void a terminal voucher.');

        $this->assertDatabaseHas('tyre_movements', [
            'id' => $movement->id,
            'status' => 'completed',
        ]);
    }

    private function createSubmittedMovement(int $preparedBy, ?int $checkedBy = null, string $status = 'submitted'): TyreMovement
    {
        $tyre = $this->createAvailableTyre();
        $store = Store::query()->firstOrFail();

        return TyreMovement::query()->create([
            'movement_no' => 'TEST-WORKFLOW-'.(++self::$tyreSequence),
            'movement_type' => 'store_to_vehicle',
            'tyre_id' => $tyre->id,
            'from_location_type' => 'store',
            'from_location_id' => $store->id,
            'to_location_type' => 'store',
            'to_location_id' => $store->id,
            'to_position_code' => 'A',
            'movement_date' => now(),
            'reason' => 'Test movement',
            'status' => $status,
            'prepared_by' => $preparedBy,
            'checked_by' => $checkedBy,
            'submitted_at' => now(),
            'checked_at' => $checkedBy ? now() : null,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);
    }

    private function createAvailableTyre(): Tyre
    {
        $store = Store::query()->firstOrFail();
        $sequence = ++self::$tyreSequence;

        return Tyre::query()->create([
            'tyre_code' => "APPROVAL-TYR-{$sequence}",
            'serial_number' => "APPROVAL-SN-{$sequence}",
            'current_location_type' => 'store',
            'current_location_id' => $store->id,
            'current_position_code' => null,
            'status' => 'available',
            'source' => 'purchased_new_tyre',
        ]);
    }
}
