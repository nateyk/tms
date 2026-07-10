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

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
    }

    public function test_create_baseline_for_new_tyre()
    {
        $tyre = Tyre::query()->where('status', 'available')->firstOrFail();

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
        $tyre = Tyre::query()->where('status', 'active')->where('current_location_id', $vehicle->id)->first();

        if (!$tyre) {
            $this->markTestSkipped('No mounted tyre found in seeder data');
        }

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

    public function test_create_baseline_for_stored_tyre()
    {
        $store = Store::query()->first();
        $tyre = Tyre::query()->where('status', 'available')->where('current_location_type', 'store')->first();

        if (!$tyre) {
            $this->markTestSkipped('No stored tyre found in seeder data');
        }

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
        $tyre = Tyre::query()->where('status', 'available')->firstOrFail();

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
        $tyre = Tyre::query()->where('status', 'available')->firstOrFail();

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
        $tyre = Tyre::query()->where('status', 'available')->firstOrFail();

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
        $tyre = Tyre::query()->where('status', 'available')->firstOrFail();

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

    public function test_delete_baseline()
    {
        $tyre = Tyre::query()->where('status', 'available')->firstOrFail();

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
}
