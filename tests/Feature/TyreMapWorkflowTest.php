<?php

namespace Tests\Feature;

use App\Enums\MovementType;
use App\Enums\PredefinedTyreLayout;
use App\Enums\TyreLocationType;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use Livewire\Livewire;
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

        $this->assertSame(VehicleTyreLayoutBuilder::LAYOUT_VERSION, $layout['layout_version']);
        $this->assertCount(10, $layout['positions']);
        $this->assertSame('P1', $layout['positions'][0]['code']);
        $this->assertSame('A', $layout['positions'][0]['display_code']);
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

    public function test_power_map_shows_spare_tyres_outside_mounted_position_count(): void
    {
        $power = Vehicle::query()
            ->where('vehicle_code', 'like', '%A14762')
            ->firstOrFail();

        $spares = \App\Models\Tyre::query()
            ->where('current_location_type', TyreLocationType::PowerVehicle)
            ->where('current_location_id', $power->id)
            ->whereIn('current_position_code', ['SPARE-W', 'SPARE-X'])
            ->orderBy('current_position_code')
            ->get();

        $this->assertCount(2, $spares);

        Livewire::test(\App\Livewire\VehicleTyreMap::class, ['vehicleId' => $power->id])
            ->assertSee('Mounted')
            ->assertSee('10/10')
            ->assertSee('Spare tyres')
            ->assertSee('1/1')
            ->assertSee('W')
            ->assertSee('SPARE-W')
            ->assertSee('KC06165J306')
            ->assertDontSee('SPARE-X')
            ->assertDontSee('G233B23074')
            ->assertDontSee('11/10');
    }

    public function test_power_map_shows_w_spare_pocket_even_when_no_spare_tyre_is_assigned(): void
    {
        $power = Vehicle::query()
            ->where('vehicle_code', 'TRK-001')
            ->firstOrFail();

        Livewire::test(\App\Livewire\VehicleTyreMap::class, ['vehicleId' => $power->id])
            ->assertSee('Spare tyres')
            ->assertSee('Power spare')
            ->assertSee('W')
            ->assertSee('No spare tyre assigned');
    }

    public function test_attached_trailer_map_shows_x_combination_spare_pocket_only(): void
    {
        $power = Vehicle::query()
            ->where('vehicle_code', 'like', '%A14762')
            ->firstOrFail();

        $trailerId = VehicleCombination::query()
            ->where('power_vehicle_id', $power->id)
            ->where('status', 'active')
            ->value('trailer_vehicle_id');

        $spares = \App\Models\Tyre::query()
            ->where('current_location_type', TyreLocationType::PowerVehicle)
            ->where('current_location_id', $power->id)
            ->whereIn('current_position_code', ['SPARE-W', 'SPARE-X'])
            ->orderBy('current_position_code')
            ->get();

        $this->assertCount(2, $spares);

        Livewire::test(\App\Livewire\VehicleTyreMap::class, ['vehicleId' => (int) $trailerId])
            ->assertSee('Spare tyres')
            ->assertSee('Combination spare')
            ->assertSee('X')
            ->assertSee('SPARE-X')
            ->assertSee('G233B23074')
            ->assertDontSee('SPARE-W')
            ->assertDontSee('KC06165J306');
    }
}
