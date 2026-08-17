<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\Tyre;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class CleanOperationalDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_preserves_accounts_roles_and_settings_while_replacing_operational_data(): void
    {
        $user = User::factory()->create([
            'email' => 'preserved@menkem.com',
        ]);
        $role = Role::findOrCreate('Preserved Role');
        $user->assignRole($role);
        SystemSetting::set('company_name', 'Menkem International Business PLC');

        $this->artisan('tms:clean-operational-data', [
            '--force' => true,
            '--seed-real-audits' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'preserved@menkem.com']);
        $this->assertTrue($user->fresh()->hasRole('Preserved Role'));
        $this->assertDatabaseHas('system_settings', [
            'key' => 'company_name',
            'value' => 'Menkem International Business PLC',
        ]);
        $this->assertSame(36, Vehicle::query()->count());
        $this->assertSame(419, Tyre::query()->count());
    }
}
