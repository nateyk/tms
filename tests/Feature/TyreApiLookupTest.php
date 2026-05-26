<?php

namespace Tests\Feature;

use App\Models\Tyre;
use App\Models\User;
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
        $tyre = Tyre::query()->where('tyre_code', 'TYR-0001')->firstOrFail();

        Sanctum::actingAs($user);

        $this->getJson('/api/tyres/TYR-0001')
            ->assertOk()
            ->assertJsonPath('tyre_code', 'TYR-0001')
            ->assertJsonPath('serial_number', $tyre->serial_number);
    }

    public function test_guest_cannot_lookup_tyre_via_api(): void
    {
        $this->seed();

        $this->getJson('/api/tyres/TYR-0001')
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
