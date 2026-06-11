<?php

namespace Tests\Feature;

use App\Models\Tyre;
use App\Models\User;
use App\Enums\TyreSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TyreApiLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_lookup_tyre_by_code(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
        $tyre = Tyre::query()->where('source', TyreSource::ExistingVehicle)->firstOrFail();

        Sanctum::actingAs($user);

        $this->getJson('/api/tyres/'.$tyre->tyre_code)
            ->assertOk()
            ->assertJsonPath('tyre_code', $tyre->tyre_code)
            ->assertJsonPath('serial_number', $tyre->serial_number);
    }

    public function test_guest_cannot_lookup_tyre_via_api(): void
    {
        $this->seed();
        $tyre = Tyre::query()->where('source', TyreSource::ExistingVehicle)->firstOrFail();

        $this->getJson('/api/tyres/'.$tyre->tyre_code)
            ->assertUnauthorized();
    }

    public function test_unknown_tyre_code_returns_not_found(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
        Sanctum::actingAs($user);

        $this->getJson('/api/tyres/TYR-NOPE')
            ->assertNotFound();
    }
}
