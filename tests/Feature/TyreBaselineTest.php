<?php

namespace Tests\Feature;

use App\Exceptions\TyreBusinessException;
use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreBaseline;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TyreBaselineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    private static int $tyreSequence = 9000;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
    }

    public function test_create_baseline_for_new_tyre()
    {
        $tyre = $this->createAvailableTyre();

        $baseline = app(\App\Services\TyreBaselineService::class)->createBaseline([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => Store::query()->first()->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->toDateString(),
            'notes' => 'Initial baseline',
        ], $this->user->id);

        $this->assertInstanceOf(TyreBaseline::class, $baseline);
        $this->assertEquals($tyre->id, $baseline->tyre_id);
        $this->assertEquals(100.00, $baseline->baseline_percentage);
        $this->assertEquals(100000, $baseline->expected_life_km);
        $this->assertEquals($this->user->id, $baseline->created_by);
    }

    public function test_create_baseline_for_mounted_tyre_with_odometer()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $tyre = $this->createMountedTyre($vehicle);

        $baseline = app(\App\Services\TyreBaselineService::class)->createBaseline([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'power_vehicle',
            'baseline_location_id' => $vehicle->id,
            'baseline_position_code' => $tyre->current_position_code,
            'baseline_odometer' => 5000,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->toDateString(),
            'notes' => null,
        ], $this->user->id);

        $this->assertInstanceOf(TyreBaseline::class, $baseline);
        $this->assertEquals(5000, $baseline->baseline_odometer);
        $this->assertEquals($vehicle->id, $baseline->baseline_location_id);
    }

    public function test_store_baseline_for_mounted_running_tyre_requires_odometer()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        \DB::table('vehicle_odometer_readings')->where('vehicle_id', $vehicle->id)->delete();
        $vehicle->forceFill(['odometer' => null])->save();
        $tyre = $this->createMountedTyre($vehicle);

        $response = $this->actingAs($this->user)->post(route('tyres.baselines.store'), [
            'tyre_id' => $tyre->id,
            'baseline_percentage' => 100,
            'expected_life_km' => 100000,
            'baseline_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('baseline_odometer');
        $this->assertDatabaseMissing('tyre_baselines', ['tyre_id' => $tyre->id]);
    }

    public function test_store_baseline_for_mounted_running_tyre_uses_vehicle_odometer_fallback()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $vehicle->forceFill(['odometer' => 146783])->save();
        $tyre = $this->createMountedTyre($vehicle);

        $this->actingAs($this->user)->post(route('tyres.baselines.store'), [
            'tyre_id' => $tyre->id,
            'baseline_percentage' => 100,
            'expected_life_km' => 100000,
            'baseline_date' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('tyre_baselines', [
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'power_vehicle',
            'baseline_location_id' => $vehicle->id,
            'baseline_position_code' => 'A',
            'baseline_odometer' => 146783,
        ]);
    }

    public function test_store_baseline_for_mounted_tyre_saves_location_position_and_odometer()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $tyre = $this->createMountedTyre($vehicle);

        $this->actingAs($this->user)->post(route('tyres.baselines.store'), [
            'tyre_id' => $tyre->id,
            'baseline_percentage' => 95,
            'expected_life_km' => 90000,
            'baseline_odometer' => 12000,
            'baseline_date' => now()->toDateString(),
            'notes' => 'Map quick baseline',
        ])->assertRedirect();

        $this->assertDatabaseHas('tyre_baselines', [
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'power_vehicle',
            'baseline_location_id' => $vehicle->id,
            'baseline_position_code' => 'A',
            'baseline_odometer' => 12000,
            'expected_life_km' => 90000,
            'notes' => 'Map quick baseline',
        ]);
    }

    public function test_store_baseline_for_spare_tyre_allows_blank_odometer()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $tyre = $this->createMountedTyre($vehicle, 'W');

        $this->actingAs($this->user)->post(route('tyres.baselines.store'), [
            'tyre_id' => $tyre->id,
            'baseline_percentage' => 100,
            'expected_life_km' => 100000,
            'baseline_date' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('tyre_baselines', [
            'tyre_id' => $tyre->id,
            'baseline_position_code' => 'W',
            'baseline_odometer' => null,
        ]);
    }

    public function test_create_baseline_for_stored_tyre()
    {
        $store = Store::query()->first();
        $tyre = $this->createAvailableTyre();

        $baseline = app(\App\Services\TyreBaselineService::class)->createBaseline([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => $store->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->toDateString(),
            'notes' => null,
        ], $this->user->id);

        $this->assertInstanceOf(TyreBaseline::class, $baseline);
        $this->assertNull($baseline->baseline_odometer);
    }

    public function test_duplicate_baseline_is_blocked()
    {
        $tyre = $this->createAvailableTyre();

        app(\App\Services\TyreBaselineService::class)->createBaseline([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => Store::query()->first()->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->toDateString(),
            'notes' => null,
        ], $this->user->id);

        $this->expectException(TyreBusinessException::class);
        $this->expectExceptionMessage('Tyre already has a baseline.');

        app(\App\Services\TyreBaselineService::class)->createBaseline([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => Store::query()->first()->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->toDateString(),
            'notes' => null,
        ], $this->user->id);
    }

    public function test_validate_baseline_percentage_range()
    {
        $tyre = $this->createAvailableTyre();

        // Test valid percentage
        $baseline = app(\App\Services\TyreBaselineService::class)->createBaseline([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => Store::query()->first()->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 50.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->toDateString(),
            'notes' => null,
        ], $this->user->id);

        $this->assertEquals(50.00, $baseline->baseline_percentage);
    }

    public function test_validate_expected_life_km_positive()
    {
        $tyre = $this->createAvailableTyre();

        $baseline = app(\App\Services\TyreBaselineService::class)->createBaseline([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => Store::query()->first()->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 150000,
            'baseline_date' => now()->toDateString(),
            'notes' => null,
        ], $this->user->id);

        $this->assertEquals(150000, $baseline->expected_life_km);
    }

    public function test_update_baseline()
    {
        $tyre = $this->createAvailableTyre();

        $baseline = app(\App\Services\TyreBaselineService::class)->createBaseline([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => Store::query()->first()->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->toDateString(),
            'notes' => null,
        ], $this->user->id);

        $updated = app(\App\Services\TyreBaselineService::class)->updateBaseline($baseline, [
            'baseline_percentage' => 80.00,
            'expected_life_km' => 120000,
            'notes' => 'Updated baseline',
        ]);

        $this->assertEquals(80.00, $updated->baseline_percentage);
        $this->assertEquals(120000, $updated->expected_life_km);
        $this->assertEquals('Updated baseline', $updated->notes);
    }

    public function test_update_baseline_route_persists_odometer_and_date()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $tyre = $this->createMountedTyre($vehicle);

        $baseline = app(\App\Services\TyreBaselineService::class)->createBaseline([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'power_vehicle',
            'baseline_location_id' => $vehicle->id,
            'baseline_position_code' => 'A',
            'baseline_odometer' => 5000,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->subDay()->toDateString(),
            'notes' => null,
        ], $this->user->id);

        $newDate = now()->toDateString();

        $this->actingAs($this->user)->put(route('tyres.baselines.update', $baseline), [
            'baseline_location_type' => 'power_vehicle',
            'baseline_location_id' => $vehicle->id,
            'baseline_position_code' => 'A',
            'baseline_odometer' => 7000,
            'baseline_percentage' => 88,
            'expected_life_km' => 85000,
            'baseline_date' => $newDate,
            'notes' => 'Updated route baseline',
        ])->assertRedirect(route('tyres.baselines.show', $baseline));

        $baseline->refresh();
        $this->assertEquals(7000, $baseline->baseline_odometer);
        $this->assertEquals(88.00, (float) $baseline->baseline_percentage);
        $this->assertEquals(85000, $baseline->expected_life_km);
        $this->assertEquals($newDate, $baseline->baseline_date->format('Y-m-d'));
        $this->assertEquals('Updated route baseline', $baseline->notes);
    }

    public function test_update_baseline_route_uses_vehicle_odometer_fallback()
    {
        $vehicle = Vehicle::query()->where('asset_type', 'power_vehicle')->firstOrFail();
        $vehicle->forceFill(['odometer' => 171742])->save();
        $tyre = $this->createMountedTyre($vehicle);

        $baseline = TyreBaseline::query()->create([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'power_vehicle',
            'baseline_location_id' => $vehicle->id,
            'baseline_position_code' => 'A',
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->subDay()->toDateString(),
            'created_by' => $this->user->id,
            'notes' => null,
        ]);

        $this->actingAs($this->user)->put(route('tyres.baselines.update', $baseline), [
            'baseline_location_type' => 'power_vehicle',
            'baseline_location_id' => $vehicle->id,
            'baseline_position_code' => 'A',
            'baseline_odometer' => null,
            'baseline_percentage' => 95,
            'expected_life_km' => 90000,
            'baseline_date' => now()->toDateString(),
            'notes' => 'Use truck KM',
        ])->assertRedirect(route('tyres.baselines.show', $baseline));

        $baseline->refresh();
        $this->assertEquals(171742, $baseline->baseline_odometer);
        $this->assertEquals(95.00, (float) $baseline->baseline_percentage);
        $this->assertEquals('Use truck KM', $baseline->notes);
    }

    public function test_delete_baseline()
    {
        $tyre = $this->createAvailableTyre();

        $baseline = app(\App\Services\TyreBaselineService::class)->createBaseline([
            'tyre_id' => $tyre->id,
            'baseline_location_type' => 'store',
            'baseline_location_id' => Store::query()->first()->id,
            'baseline_position_code' => null,
            'baseline_odometer' => null,
            'baseline_percentage' => 100.00,
            'expected_life_km' => 100000,
            'baseline_date' => now()->toDateString(),
            'notes' => null,
        ], $this->user->id);

        $result = app(\App\Services\TyreBaselineService::class)->deleteBaseline($baseline);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('tyre_baselines', ['id' => $baseline->id]);
    }

    private function createAvailableTyre(): Tyre
    {
        $store = Store::query()->firstOrFail();
        $sequence = ++self::$tyreSequence;

        return Tyre::query()->create([
            'tyre_code' => "TEST-TYR-{$sequence}",
            'serial_number' => "TEST-SN-{$sequence}",
            'current_location_type' => 'store',
            'current_location_id' => $store->id,
            'current_position_code' => null,
            'status' => 'available',
            'source' => 'purchased_new_tyre',
        ]);
    }

    private function createMountedTyre(Vehicle $vehicle, string $position = 'A'): Tyre
    {
        $sequence = ++self::$tyreSequence;

        return Tyre::query()->create([
            'tyre_code' => "TEST-TYR-{$sequence}",
            'serial_number' => "TEST-SN-{$sequence}",
            'current_location_type' => $vehicle->asset_type->value,
            'current_location_id' => $vehicle->id,
            'current_position_code' => $position,
            'status' => 'active',
            'source' => 'purchased_new_tyre',
        ]);
    }
}
