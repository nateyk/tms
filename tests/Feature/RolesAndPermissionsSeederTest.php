<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesAndPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_accounts_have_separated_voucher_permissions(): void
    {
        $this->seed();

        $storeKeeper = User::query()->where('email', 'storekeeper@menkem.com')->firstOrFail();
        $technicalHead = User::query()->where('email', 'demmelash.fetene@menkemintl.com')->firstOrFail();
        $companyManager = User::query()->where('email', 'manager@menkem.com')->firstOrFail();
        $superAdmin = User::query()->where('email', 'admin@menkem.com')->firstOrFail();

        $this->assertTrue($storeKeeper->hasRole('Store Keeper'));
        $this->assertTrue($storeKeeper->can('tyre.create'));
        $this->assertTrue($storeKeeper->can('vehicle.odometer.update'));
        $this->assertTrue($storeKeeper->can('movement.create'));
        $this->assertFalse($storeKeeper->can('movement.check'));
        $this->assertFalse($storeKeeper->can('movement.approve'));

        $this->assertTrue($technicalHead->hasRole('Technic and Maintenance Head'));
        $this->assertFalse($technicalHead->can('movement.create'));
        $this->assertTrue($technicalHead->can('movement.check'));
        $this->assertTrue($technicalHead->can('disposal.check'));
        $this->assertFalse($technicalHead->can('movement.approve'));
        $this->assertFalse($technicalHead->can('fleet.manage'));
        $this->assertFalse($technicalHead->can('tyre.update'));
        $this->assertFalse($technicalHead->can('disposal.create'));
        $this->assertFalse($technicalHead->can('vehicle.update'));
        $this->assertFalse($technicalHead->can('trailer.transfer'));
        $this->assertFalse($technicalHead->can('audit.view'));
        $this->assertFalse($technicalHead->can('settings.manage'));

        $this->assertTrue($companyManager->hasRole('Company Manager'));
        $this->assertTrue($companyManager->can('movement.approve'));
        $this->assertTrue($companyManager->can('movement.reject'));
        $this->assertFalse($companyManager->can('movement.create'));
        $this->assertFalse($companyManager->can('tyre.update'));

        $this->assertTrue($superAdmin->hasRole('Super Admin'));
        $this->assertTrue($superAdmin->can('settings.manage'));
    }
}
