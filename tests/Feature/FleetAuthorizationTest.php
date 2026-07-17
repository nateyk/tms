<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->viewer = User::query()->where('email', 'manager@menkem.com')->firstOrFail();
    }

    public function test_view_only_manager_cannot_mutate_fleet_setup(): void
    {
        $this->actingAs($this->viewer)
            ->get(route('fleet.vehicles.create'))
            ->assertForbidden();

        $this->actingAs($this->viewer)
            ->get(route('fleet.vehicle-types.create'))
            ->assertForbidden();

        $this->actingAs($this->viewer)
            ->get(route('fleet.stores.create'))
            ->assertForbidden();

        $this->actingAs($this->viewer)
            ->post(route('fleet.vehicles.store'), [])
            ->assertForbidden();

        $this->actingAs($this->viewer)
            ->post(route('fleet.vehicle-types.store'), [])
            ->assertForbidden();

        $this->actingAs($this->viewer)
            ->post(route('fleet.stores.store'), [])
            ->assertForbidden();
    }
}
