<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_admin_can_access_dashboard(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_user_without_operational_role_cannot_access_protected_module(): void
    {
        $this->seed();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/tyres')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }
}
