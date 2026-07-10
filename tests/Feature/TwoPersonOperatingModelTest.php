<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoPersonOperatingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_creates_store_and_company_manager_accounts(): void
    {
        $this->seed();

        $store = User::query()->where('email', 'store@menkem.com')->firstOrFail();
        $company = User::query()->where('email', 'manager@menkem.com')->firstOrFail();

        $this->assertTrue($store->hasRole('Store Manager'));
        $this->assertTrue($company->hasRole('Company Manager'));
    }

    public function test_store_manager_can_operate_store_work_but_cannot_approve_or_manage_system(): void
    {
        $this->seed();

        $store = User::query()->where('email', 'store@menkem.com')->firstOrFail();

        $this->assertTrue($store->can('tyre.view'));
        $this->assertTrue($store->can('tyre.create'));
        $this->assertTrue($store->can('tyre.update'));
        $this->assertTrue($store->can('vehicle.view'));
        $this->assertTrue($store->can('vehicle.update'));
        $this->assertTrue($store->can('trailer.assign'));
        $this->assertTrue($store->can('trailer.transfer'));
        $this->assertTrue($store->can('movement.create'));
        $this->assertTrue($store->can('movement.check'));
        $this->assertTrue($store->can('disposal.create'));
        $this->assertTrue($store->can('disposal.check'));

        $this->assertFalse($store->can('tyre.approve'));
        $this->assertFalse($store->can('movement.approve'));
        $this->assertFalse($store->can('movement.reject'));
        $this->assertFalse($store->can('disposal.approve'));
        $this->assertFalse($store->can('disposal.reject'));
        $this->assertFalse($store->can('settings.manage'));
        $this->assertFalse($store->can('audit.view'));
    }

    public function test_company_manager_can_approve_all_work_but_cannot_enter_store_transactions(): void
    {
        $this->seed();

        $company = User::query()->where('email', 'manager@menkem.com')->firstOrFail();

        $this->assertTrue($company->can('tyre.view'));
        $this->assertTrue($company->can('tyre.approve'));
        $this->assertTrue($company->can('vehicle.view'));
        $this->assertTrue($company->can('movement.approve'));
        $this->assertTrue($company->can('movement.reject'));
        $this->assertTrue($company->can('disposal.approve'));
        $this->assertTrue($company->can('disposal.reject'));
        $this->assertTrue($company->can('report.view'));
        $this->assertTrue($company->can('report.export'));
        $this->assertTrue($company->can('audit.view'));

        $this->assertFalse($company->can('tyre.create'));
        $this->assertFalse($company->can('tyre.update'));
        $this->assertFalse($company->can('vehicle.create'));
        $this->assertFalse($company->can('vehicle.update'));
        $this->assertFalse($company->can('trailer.assign'));
        $this->assertFalse($company->can('trailer.transfer'));
        $this->assertFalse($company->can('movement.create'));
        $this->assertFalse($company->can('movement.check'));
        $this->assertFalse($company->can('disposal.create'));
        $this->assertFalse($company->can('disposal.check'));
        $this->assertFalse($company->can('settings.manage'));
    }

    public function test_operating_users_can_access_dashboard(): void
    {
        $this->seed();

        $store = User::query()->where('email', 'store@menkem.com')->firstOrFail();
        $company = User::query()->where('email', 'manager@menkem.com')->firstOrFail();

        $this->actingAs($store)->get('/dashboard')->assertOk();
        $this->actingAs($company)->get('/dashboard')->assertOk();
    }
}
