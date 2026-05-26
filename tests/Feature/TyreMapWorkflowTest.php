<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\PredefinedTyreLayout;
use App\Models\Vehicle;
use App\Services\TyreMapWorkflowService;
use App\Services\VehicleTyreLayoutBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TyreMapWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_predefined_layout_generates_position_codes(): void
    {
        $layout = app(VehicleTyreLayoutBuilder::class)->buildLayout(10, 3, 'P');

        $this->assertSame(3, $layout['layout_version']);
        $this->assertCount(10, $layout['positions']);
        $this->assertSame('P1', $layout['positions'][0]['code']);
        $this->assertSame('single', $layout['positions'][0]['dual']);
        $this->assertSame('outer', $layout['positions'][2]['dual']);
    }

    public function test_empty_positions_for_partially_filled_truck(): void
    {
        $vehicle = Vehicle::query()->where('vehicle_code', 'TRK-001')->firstOrFail();
        $empty = app(TyreMapWorkflowService::class)->emptyPositions($vehicle);

        $this->assertGreaterThan(0, $empty->count());
        $this->assertTrue($empty->contains('code', 'P7'));
    }

    public function test_install_url_prefills_movement_form(): void
    {
        $admin = \App\Models\User::query()->where('email', 'admin@menkem.com')->firstOrFail();
        $vehicle = Vehicle::query()->where('vehicle_code', 'TRK-001')->firstOrFail();
        $url = app(TyreMapWorkflowService::class)->installMovementUrl($vehicle, 'P7');

        $this->assertStringContainsString('vehicle_id='.$vehicle->id, $url);
        $this->assertStringContainsString('position=P7', $url);
        $this->assertStringContainsString(MovementType::StoreToVehicle->value, $url);

        $this->actingAs($admin)->get($url)->assertOk();
    }

    public function test_prefill_from_request_query(): void
    {
        $admin = \App\Models\User::query()->where('email', 'admin@menkem.com')->firstOrFail();
        $vehicle = Vehicle::query()->where('vehicle_code', 'TRK-008')->firstOrFail();

        $this->actingAs($admin)->get('/admin/tyre-movements/create?'.http_build_query([
            'vehicle_id' => $vehicle->id,
            'position' => 'P3',
        ]))->assertOk();

        $prefill = app(TyreMapWorkflowService::class)->prefilledMovementFromRequest();
        $this->assertSame($vehicle->id, $prefill['to_location_id']);
        $this->assertSame('P3', $prefill['to_position_code']);
        $this->assertSame(MovementType::StoreToVehicle->value, $prefill['movement_type']);
    }

    public function test_predefined_enum_matches_builder_counts(): void
    {
        foreach (PredefinedTyreLayout::cases() as $preset) {
            $layout = app(VehicleTyreLayoutBuilder::class)->buildLayout(
                $preset->tyreCount(),
                $preset->axleCount(),
                $preset->positionPrefix(),
            );
            $this->assertCount($preset->tyreCount(), $layout['positions']);
        }
    }
}
