<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreMovement;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TyreMovementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->adminUser = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
    }

    public function test_create_page_lists_active_destination_vehicles_with_availability_counts(): void
    {
        $active = $this->vehicle('MOVE-ACTIVE', 'power_vehicle', 'active', 120000);
        $inactive = $this->vehicle('MOVE-INACTIVE', 'power_vehicle', 'inactive', 90000);
        $this->mountedTyre($active, 'A');

        $this->actingAs($this->adminUser)
            ->get(route('tyres.movements.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('tyres/movements/create')
                ->where('powerVehicles', fn ($vehicles) => collect($vehicles)->contains(
                    fn (array $vehicle) => $vehicle['id'] === $active->id
                        && $vehicle['mounted_count'] === 1
                        && $vehicle['available_position_count'] > 0
                ))
                ->where('powerVehicles', fn ($vehicles) => collect($vehicles)->doesntContain(
                    fn (array $vehicle) => $vehicle['id'] === $inactive->id
                ))
            );
    }

    public function test_inline_form_options_endpoint_returns_complete_movement_payload(): void
    {
        $this->actingAs($this->adminUser)
            ->getJson(route('tyres.movements.form-options'))
            ->assertOk()
            ->assertJsonStructure([
                'tyres',
                'stores',
                'powerVehicles',
                'trailers',
                'destinationTypes',
                'destinationTargets',
            ])
            ->assertJsonPath('destinationTargets.0.value', 'store')
            ->assertJsonPath('destinationTargets.1.value', 'vehicle_unit');
    }

    public function test_vehicle_map_payload_contains_inline_mount_and_move_actions(): void
    {
        $vehicle = $this->vehicle('INLINE-MAP-VEHICLE', 'power_vehicle', 'active', 150000, 24, 6);
        $this->mountedTyre($vehicle, 'A');

        $this->actingAs($this->adminUser)
            ->get(route('fleet.vehicles.show', $vehicle))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tyreMap.konvaConfig.slots', fn ($slots) => collect($slots)->contains(
                    fn (array $slot) => $slot['display_code'] === 'A' && filled($slot['tyre_id']) && filled($slot['create_movement_url'])
                ))
                ->where('tyreMap.konvaConfig.slots', fn ($slots) => collect($slots)->contains(
                    fn (array $slot) => $slot['display_code'] === 'B' && $slot['tyre_id'] === null && filled($slot['create_movement_url'])
                ))
            );
    }

    public function test_map_fill_url_prefills_destination_and_creates_store_to_vehicle_draft(): void
    {
        $tyre = $this->storeTyre('MAP-FILL-001');
        $vehicle = $this->vehicle('MAP-FILL-DEST', 'power_vehicle', 'active', 172842);

        $this->actingAs($this->adminUser)
            ->get(route('tyres.movements.create', [
                'vehicle_id' => $vehicle->id,
                'position' => 'B',
                'movement_type' => 'store_to_vehicle',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('tyres/movements/create')
                ->where('prefilled.to_location_id', $vehicle->id)
                ->where('prefilled.to_position_code', 'B')
                ->where('prefilled.to_location_type', 'power_vehicle')
            );

        $this->actingAs($this->adminUser)
            ->post(route('tyres.movements.store'), [
                'tyre_id' => $tyre->id,
                'movement_date' => now()->toDateString(),
                'to_location_type' => 'power_vehicle',
                'to_location_id' => $vehicle->id,
                'to_position_code' => 'B',
                'to_odometer' => 172842,
                'reason' => 'Install tyre at B on MAP-FILL-DEST',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tyre_movements', [
            'tyre_id' => $tyre->id,
            'to_location_id' => $vehicle->id,
            'to_position_code' => 'B',
            'status' => 'draft',
        ]);
    }

    public function test_tyre_detail_movement_url_prefills_source_vehicle_odometer(): void
    {
        $source = $this->vehicle('DETAIL-SOURCE', 'power_vehicle', 'active', 175842);
        $tyre = $this->mountedTyre($source, 'A', 170000);

        $this->actingAs($this->adminUser)
            ->get(route('tyres.movements.create', [
                'source_location_type' => 'power_vehicle',
                'source_position' => 'A',
                'source_vehicle_id' => $source->id,
                'tyre_id' => $tyre->id,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('tyres/movements/create')
                ->where('prefilled.tyre_id', $tyre->id)
                ->where('prefilled.from_odometer', 175842)
                ->where('prefilled.reason', 'Move tyre from A')
            );
    }

    public function test_destination_positions_return_empty_occupied_and_spare_status(): void
    {
        $vehicle = $this->vehicle('MOVE-POSITIONS', 'power_vehicle', 'active', 130000, 24, 6);
        $this->mountedTyre($vehicle, 'A');

        $this->actingAs($this->adminUser)
            ->getJson(route('tyres.movements.position-options', $vehicle))
            ->assertOk()
            ->assertJsonFragment([
                'code' => 'A',
                'is_empty' => false,
                'is_occupied' => true,
            ])
            ->assertJsonFragment([
                'code' => 'W',
                'type' => 'spare',
                'is_empty' => true,
            ]);
    }

    public function test_combined_destination_returns_power_and_attached_trailer_positions(): void
    {
        $power = $this->vehicle('COMBINED-POWER', 'power_vehicle', 'active', 175842, 24, 6);
        $trailer = $this->vehicle('COMBINED-TRAILER', 'trailer', 'active', 90000, 12, 3);
        VehicleCombination::query()->create([
            'power_vehicle_id' => $power->id,
            'trailer_vehicle_id' => $trailer->id,
            'attached_date' => now()->toDateString(),
            'odometer_at_attach' => $power->odometer,
            'status' => 'active',
            'attached_by' => $this->adminUser->id,
        ]);

        $this->actingAs($this->adminUser)
            ->get(route('tyres.movements.create'))
            ->assertInertia(fn ($page) => $page
                ->where('powerVehicles', fn ($vehicles) => collect($vehicles)->contains(
                    fn (array $vehicle) => $vehicle['id'] === $power->id
                        && $vehicle['attached_trailer']['id'] === $trailer->id
                        && $vehicle['trailer_available_count'] > 0
                ))
            );

        $this->actingAs($this->adminUser)
            ->getJson(route('tyres.movements.position-options', $power))
            ->assertJsonFragment([
                'owner_type' => 'power_vehicle',
                'owner_vehicle_id' => $power->id,
            ])
            ->assertJsonFragment([
                'owner_type' => 'trailer',
                'owner_vehicle_id' => $trailer->id,
            ]);
    }

    public function test_same_vehicle_position_change_can_be_drafted_without_moving_tyre(): void
    {
        $power = $this->vehicle('ROTATION-POWER', 'power_vehicle', 'active', 175000, 24, 6);
        $tyre = $this->mountedTyre($power, 'B', 170000);

        $this->actingAs($this->adminUser)
            ->post(route('tyres.movements.store'), [
                'tyre_id' => $tyre->id,
                'movement_date' => now()->toDateString(),
                'to_location_type' => 'power_vehicle',
                'to_location_id' => $power->id,
                'to_position_code' => 'J',
                'from_odometer' => 175000,
                'to_odometer' => 175000,
            ])
            ->assertRedirect();

        $tyre->refresh();
        $this->assertSame('B', $tyre->current_position_code);
        $this->assertDatabaseHas('tyre_movements', [
            'tyre_id' => $tyre->id,
            'to_location_type' => 'power_vehicle',
            'to_location_id' => $power->id,
            'to_position_code' => 'J',
            'movement_type' => 'position_change_same_asset',
            'status' => 'draft',
        ]);
    }

    public function test_power_to_attached_trailer_movement_can_be_drafted_with_owner_fields(): void
    {
        $power = $this->vehicle('POWER-TO-TRAILER', 'power_vehicle', 'active', 175000, 24, 6);
        $trailer = $this->vehicle('ATTACHED-TRAILER', 'trailer', 'active', 90000, 12, 3);
        VehicleCombination::query()->create([
            'power_vehicle_id' => $power->id,
            'trailer_vehicle_id' => $trailer->id,
            'attached_date' => now()->toDateString(),
            'odometer_at_attach' => $power->odometer,
            'status' => 'active',
            'attached_by' => $this->adminUser->id,
        ]);
        $tyre = $this->mountedTyre($power, 'B', 170000);

        $this->actingAs($this->adminUser)
            ->post(route('tyres.movements.store'), [
                'tyre_id' => $tyre->id,
                'movement_date' => now()->toDateString(),
                'to_location_type' => 'trailer',
                'to_location_id' => $trailer->id,
                'to_position_code' => 'C',
                'from_odometer' => 175000,
                'to_odometer' => 90000,
            ])
            ->assertRedirect();

        $tyre->refresh();
        $this->assertSame($power->id, $tyre->current_location_id);
        $this->assertSame('B', $tyre->current_position_code);
        $this->assertDatabaseHas('tyre_movements', [
            'tyre_id' => $tyre->id,
            'to_location_type' => 'trailer',
            'to_location_id' => $trailer->id,
            'to_position_code' => 'C',
            'status' => 'draft',
        ]);
    }

    public function test_unattached_trailer_cannot_receive_a_tyre(): void
    {
        $trailer = $this->vehicle('UNATTACHED-TRAILER', 'trailer', 'active', 90000, 12, 3);
        $tyre = $this->storeTyre('UNATTACHED-DESTINATION-TYRE');

        $this->actingAs($this->adminUser)
            ->post(route('tyres.movements.store'), [
                'tyre_id' => $tyre->id,
                'movement_date' => now()->toDateString(),
                'to_location_type' => 'trailer',
                'to_location_id' => $trailer->id,
                'to_position_code' => 'A',
                'to_odometer' => 90000,
            ])
            ->assertSessionHasErrors('to_location_id');
    }

    public function test_occupied_destination_position_cannot_be_selected(): void
    {
        $storeTyre = $this->storeTyre('MOVE-STORE-001');
        $vehicle = $this->vehicle('MOVE-OCCUPIED', 'power_vehicle', 'active', 140000);
        $this->mountedTyre($vehicle, 'A');

        $this->actingAs($this->adminUser)
            ->post(route('tyres.movements.store'), [
                'tyre_id' => $storeTyre->id,
                'movement_date' => now()->toDateString(),
                'to_location_type' => 'power_vehicle',
                'to_location_id' => $vehicle->id,
                'to_position_code' => 'A',
                'to_odometer' => 141000,
            ])
            ->assertSessionHasErrors('to_position_code');
    }

    public function test_vehicle_to_vehicle_movement_requires_source_and_destination_odometer(): void
    {
        $source = $this->vehicle('MOVE-SOURCE', 'power_vehicle', 'active', 150000);
        $destination = $this->vehicle('MOVE-DEST', 'power_vehicle', 'active', 160000);
        $tyre = $this->mountedTyre($source, 'A', 149000);

        $this->actingAs($this->adminUser)
            ->post(route('tyres.movements.store'), [
                'tyre_id' => $tyre->id,
                'movement_date' => now()->toDateString(),
                'to_location_type' => 'power_vehicle',
                'to_location_id' => $destination->id,
                'to_position_code' => 'B',
            ])
            ->assertSessionHasErrors(['from_odometer', 'to_odometer']);
    }

    public function test_vehicle_to_store_requires_source_odometer_only_and_does_not_move_tyre_on_draft(): void
    {
        $source = $this->vehicle('MOVE-SOURCE-STORE', 'power_vehicle', 'active', 170000);
        $store = Store::query()->firstOrFail();
        $tyre = $this->mountedTyre($source, 'A', 169000);

        $this->actingAs($this->adminUser)
            ->post(route('tyres.movements.store'), [
                'tyre_id' => $tyre->id,
                'movement_date' => now()->toDateString(),
                'to_location_type' => 'store',
                'to_location_id' => $store->id,
                'from_odometer' => 170500,
            ])
            ->assertRedirect();

        $tyre->refresh();

        $this->assertEquals('power_vehicle', $tyre->current_location_type->value);
        $this->assertEquals($source->id, $tyre->current_location_id);
        $this->assertEquals('A', $tyre->current_position_code);
        $this->assertDatabaseHas('tyre_assignments', [
            'tyre_id' => $tyre->id,
            'asset_id' => $source->id,
            'position_code' => 'A',
            'status' => 'active',
        ]);
    }

    public function test_store_to_vehicle_requires_destination_odometer_only_for_running_position(): void
    {
        $tyre = $this->storeTyre('MOVE-STORE-002');
        $destination = $this->vehicle('MOVE-STORE-DEST', 'power_vehicle', 'active', 180000);

        $this->actingAs($this->adminUser)
            ->post(route('tyres.movements.store'), [
                'tyre_id' => $tyre->id,
                'movement_date' => now()->toDateString(),
                'to_location_type' => 'power_vehicle',
                'to_location_id' => $destination->id,
                'to_position_code' => 'A',
            ])
            ->assertSessionHasErrors('to_odometer')
            ->assertSessionDoesntHaveErrors('from_odometer');
    }

    public function test_pending_tyre_cannot_create_new_movement(): void
    {
        $tyre = $this->storeTyre('MOVE-PENDING');
        $destination = $this->vehicle('MOVE-PENDING-DEST', 'power_vehicle', 'active', 190000);

        TyreMovement::query()->create([
            'movement_no' => 'MOV-PENDING-TEST',
            'movement_type' => 'store_to_vehicle',
            'tyre_id' => $tyre->id,
            'from_location_type' => 'store',
            'from_location_id' => $tyre->current_location_id,
            'to_location_type' => 'power_vehicle',
            'to_location_id' => $destination->id,
            'to_position_code' => 'A',
            'movement_date' => now()->toDateString(),
            'status' => 'draft',
            'prepared_by' => $this->adminUser->id,
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('tyres.movements.store'), [
                'tyre_id' => $tyre->id,
                'movement_date' => now()->toDateString(),
                'to_location_type' => 'power_vehicle',
                'to_location_id' => $destination->id,
                'to_position_code' => 'B',
                'to_odometer' => 191000,
            ])
            ->assertSessionHasErrors('tyre_id');
    }

    private function vehicle(
        string $code,
        string $assetType = 'power_vehicle',
        string $status = 'active',
        int $odometer = 100000,
        int $tyreCount = 6,
        int $axleCount = 2,
    ): Vehicle {
        $vehicleType = VehicleType::query()->create([
            'name' => "{$code} Type",
            'asset_type' => $assetType,
            'axle_count' => $axleCount,
            'tyre_count' => $tyreCount,
            'status' => 'active',
        ]);

        return Vehicle::query()->create([
            'vehicle_code' => $code,
            'plate_number' => $code,
            'asset_type' => $assetType,
            'vehicle_type_id' => $vehicleType->id,
            'odometer' => $odometer,
            'status' => $status,
        ]);
    }

    private function storeTyre(string $code): Tyre
    {
        $store = Store::query()->firstOrFail();

        return Tyre::query()->create([
            'tyre_code' => $code,
            'serial_number' => "{$code}-SN",
            'current_location_type' => 'store',
            'current_location_id' => $store->id,
            'status' => 'available',
            'source' => 'purchased_new_tyre',
        ]);
    }

    private function mountedTyre(Vehicle $vehicle, string $positionCode, int $installedOdometer = 100000): Tyre
    {
        $tyre = Tyre::query()->create([
            'tyre_code' => "TYR-{$vehicle->vehicle_code}-{$positionCode}",
            'serial_number' => "SN-{$vehicle->vehicle_code}-{$positionCode}",
            'current_location_type' => $vehicle->asset_type->value === 'trailer' ? 'trailer' : 'power_vehicle',
            'current_location_id' => $vehicle->id,
            'current_position_code' => $positionCode,
            'status' => 'active',
            'source' => 'purchased_new_tyre',
        ]);

        TyreAssignment::query()->create([
            'tyre_id' => $tyre->id,
            'asset_type' => $vehicle->asset_type->value === 'trailer' ? 'trailer' : 'power_vehicle',
            'asset_id' => $vehicle->id,
            'position_code' => $positionCode,
            'installed_date' => now()->toDateString(),
            'installed_odometer' => $installedOdometer,
            'status' => 'active',
            'installed_by' => $this->adminUser->id,
        ]);

        return $tyre;
    }
}
