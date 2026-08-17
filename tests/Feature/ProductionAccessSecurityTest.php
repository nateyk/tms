<?php

namespace Tests\Feature;

use App\Enums\TyreLocationType;
use App\Enums\TyreSource;
use App\Enums\TyreStatus;
use App\Models\Tyre;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionAccessSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_tyre_scan_requires_authentication_and_tyre_view_permission(): void
    {
        $this->withoutVite();

        $tyre = Tyre::query()->create([
            'tyre_code' => 'TYR-SEC-001',
            'serial_number' => 'SER-SEC-001',
            'source' => TyreSource::PurchasedNewTyre,
            'current_location_type' => TyreLocationType::Store,
            'status' => TyreStatus::Available,
        ]);

        $this->get(route('tyres.scan', $tyre->tyre_code))
            ->assertRedirect(route('login'));

        $roleless = User::factory()->create();
        $this->actingAs($roleless)
            ->get(route('tyres.scan', $tyre->tyre_code))
            ->assertForbidden();

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('tyre.view');

        $this->actingAs($viewer)
            ->get(route('tyres.scan', $tyre->tyre_code))
            ->assertOk();
    }

    public function test_web_responses_include_baseline_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_admin_can_deactivate_operator_but_accounts_are_not_hard_deleted(): void
    {
        $admin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
        $operator = User::query()->where('email', 'storekeeper@menkem.com')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $operator), [
                'name' => $operator->name,
                'email' => $operator->email,
                'password' => '',
                'roles' => ['Store Keeper'],
                'is_active' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($operator->refresh()->is_active);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $operator))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $operator->id]);
    }

    public function test_last_active_administrator_cannot_be_deactivated_or_demoted(): void
    {
        $admin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();
        $settingsManager = User::factory()->create();
        $settingsManager->givePermissionTo('settings.manage');

        $response = $this->actingAs($settingsManager)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => '',
                'roles' => ['Management Viewer'],
                'is_active' => false,
            ]);

        $response->assertSessionHas('error');
        $this->assertTrue($admin->refresh()->is_active);
        $this->assertTrue($admin->hasRole('Super Admin'));
    }
}
