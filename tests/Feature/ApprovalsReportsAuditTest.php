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
    private static int $tyreSequence = 11000;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->adminUser = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
        
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
            'prepared_by' => $this->adminUser->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('approvals.pending'));

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            return $page->component('approvals/pending')
                ->has('movements')
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
            'prepared_by' => $this->adminUser->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
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
            'prepared_by' => $this->adminUser->id,
            'checked_by' => $this->adminUser->id,
            'checked_at' => now(),
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
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
            'prepared_by' => $this->adminUser->id,
            'checked_by' => $this->adminUser->id,
            'checked_at' => now(),
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
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
            'prepared_by' => $this->adminUser->id,
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
            'prepared_by' => $this->adminUser->id,
            'approved_by' => $this->adminUser->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('tyres.movements.cancel', $movement->id))
            ->assertRedirect(route('tyres.movements.index'));

        $this->assertDatabaseHas('tyre_movements', [
            'id' => $movement->id,
            'status' => 'cancelled',
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
