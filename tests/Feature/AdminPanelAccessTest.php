<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_role_user_can_access_admin_panel(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_user_without_operational_role_cannot_access_admin_panel(): void
    {
        $this->seed();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }
}
