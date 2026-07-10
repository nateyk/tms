<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\PredefinedTyreLayout;
use App\Enums\TyreLocationType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TyreMapWorkflowService;
use App\Services\TyreMovementService;
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

        $this->assertSame(VehicleTyreLayoutBuilder::LAYOUT_VERSION, $layout['layout_version']);
        $this->assertCount(10, $layout['positions']);
        $this->assertSame('A', $layout['positions'][0]['code']);
        $this->assertSame('A', $layout['positions'][0]['display_code']);
        $this->assertSame('P1', $layout['positions'][0]['legacy_code']);
        $this->assertSame('single', $layout['positions'][0]['dual']);
        $this->assertSame('outer', $layout['positions'][2]['dual']);
    }

    public function test_empty_positions_for_partially_filled_truck(): void
    {
        $vehicle = Vehicle::query()->where('vehicle_code', 'TRK-001')->firstOrFail();
        $empty = app(TyreMapWorkflowService::class)->emptyPositions($vehicle);

        $this->assertGreaterThan(0, $empty->count());
        $this->assertTrue($empty->contains('code', 'G'));
    }

    public function test_install_url_prefills_movement_form(): void
    {
        $admin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
        $vehicle = Vehicle::query()->where('vehicle_code', 'TRK-001')->firstOrFail();
        $url = app(TyreMapWorkflowService::class)->installMovementUrl($vehicle, 'P7');

        $this->assertStringContainsString('vehicle_id='.$vehicle->id, $url);
        $this->assertStringContainsString('position=P7', $url);
        $this->assertStringContainsString(MovementType::StoreToVehicle->value, $url);

        $this->actingAs($admin)->get($url)->assertOk();
    }

    public function test_prefill_from_request_query(): void
    {
        $admin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
        $vehicle = Vehicle::query()->where('vehicle_code', 'TRK-008')->firstOrFail();

        $this->actingAs($admin)->get('/tyres/movements/create?'.http_build_query([
            'vehicle_id' => $vehicle->id,
            'position' => 'P3',
        ]))->assertOk();

        $prefill = app(TyreMapWorkflowService::class)->prefilledMovementFromRequest();
        $this->assertSame($vehicle->id, $prefill['to_location_id']);
        $this->assertSame('P3', $prefill['to_position_code']);
        $this->assertSame(MovementType::StoreToVehicle->value, $prefill['movement_type']);
    }

    public function test_create_movement_derives_movement_type_from_source_and_destination(): void
    {
        $admin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
        $storeId = \App\Models\Store::query()->value('id');
        $tyre = \App\Models\Tyre::query()->create([
            'tyre_code' => 'TYR-DERIVE-001',
            'serial_number' => 'SN-DERIVE-001',
            'source' => \App\Enums\TyreSource::PurchasedNewTyre,
            'current_location_type' => TyreLocationType::Store,
            'current_location_id' => $storeId,
            'status' => \App\Enums\TyreStatus::Available,
        ]);
        $vehicle = Vehicle::query()->where('vehicle_code', 'TRK-001')->firstOrFail();

        $movement = app(TyreMovementService::class)->createDraft([
            'movement_date' => now()->toDateString(),
            'tyre_id' => $tyre->id,
            'to_location_type' => TyreLocationType::PowerVehicle,
            'to_location_id' => $vehicle->id,
            'to_position_code' => 'P7',
            'reason' => 'Fit available tyre to open vehicle position.',
        ], $admin->id);

        $this->assertSame(MovementType::StoreToVehicle, $movement->movement_type);
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
