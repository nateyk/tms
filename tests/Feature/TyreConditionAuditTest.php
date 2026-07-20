<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBaseline;
use App\Models\TyreInspection;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TyreConditionAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
    }

    public function test_audit_stores_checkpoint_context_and_drives_effective_remaining(): void
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $vehicle->forceFill(['odometer' => 1000])->save();
        $store = Store::query()->firstOrFail();
        $tyre = Tyre::query()->create([
            'tyre_code' => 'AUDIT-TYR-0001',
            'serial_number' => 'AUDIT-SN-0001',
            'current_location_type' => 'power_vehicle',
            'current_location_id' => $vehicle->id,
            'current_position_code' => 'A',
            'status' => 'available',
            'source' => 'purchased_new_tyre',
        ]);

        TyreBaseline::query()->create([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'power_vehicle',
            'baseline_location_id' => $vehicle->id,
            'baseline_position_code' => 'A',
            'baseline_odometer' => 0,
            'baseline_percentage' => 95,
            'expected_life_km' => 100000,
            'baseline_date' => now()->subDay()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        TyreAssignment::query()->create([
            'tyre_id' => $tyre->id,
            'asset_type' => 'power_vehicle',
            'asset_id' => $vehicle->id,
            'position_code' => 'A',
            'installed_odometer' => 0,
            'installed_date' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($this->user)->post(route('tyres.condition-audits.store', $tyre), [
            'audited_remaining_percentage' => 90,
            'inspection_date' => now()->toDateString(),
            'audit_odometer' => 1200,
            'tread_depth' => 12.5,
            'condition' => 'Watch',
            'reason' => 'Uneven wear',
            'notes' => 'Inner shoulder wear noted.',
        ])->assertRedirect(route('tyres.show', $tyre));

        $audit = TyreInspection::query()->where('tyre_id', $tyre->id)->firstOrFail();
        $this->assertSame($this->user->id, $audit->audited_by);
        $this->assertSame($this->user->id, $audit->inspected_by);
        $this->assertSame($vehicle->id, $audit->vehicle_id);
        $this->assertSame('A', $audit->position_code);
        $this->assertSame(1200, $audit->audit_odometer);
        $this->assertEquals(1200, $vehicle->fresh()->odometer);
        $this->assertSame(93.8, (float) $audit->calculated_remaining_percentage_at_audit);
        $this->assertSame(-3.8, (float) $audit->variance_percentage);
        $this->assertSame(-3.8, round((float) $audit->audited_remaining_percentage - (float) $audit->calculated_remaining_percentage_at_audit, 2));
        $this->assertSame('Uneven wear', $audit->reason);
        $this->assertNotNull($audit->created_at);

        $usage = app(\App\Services\TyreUsageTrackingService::class)->calculateTyreUsage($tyre->fresh());
        $this->assertSame(90.0, $usage['effective_remaining_percentage']);
        $this->assertSame(95.0, $usage['baseline_percentage']);

        app(\App\Services\VehicleOdometerService::class)->updateOdometer(
            $vehicle->fresh(),
            4600,
            'manual',
            null,
            $this->user->id,
            'Follow-up vehicle KM reading',
        );
        $usageAfterKm = app(\App\Services\TyreUsageTrackingService::class)->calculateTyreUsage($tyre->fresh());
        $this->assertSame(86.6, $usageAfterKm['effective_remaining_percentage']);
        $this->assertSame(95.0, $usageAfterKm['baseline_percentage']);

        $this->assertDatabaseHas('tyre_inspections', [
            'id' => $audit->id,
            'audited_by' => $this->user->id,
            'vehicle_id' => $vehicle->id,
            'position_code' => 'A',
            'audit_odometer' => 1200,
        ]);
    }
}
