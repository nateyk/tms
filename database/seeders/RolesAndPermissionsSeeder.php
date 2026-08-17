<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'tyre.view', 'tyre.create', 'tyre.update', 'tyre.delete', 'tyre.approve',
            'vehicle.view', 'vehicle.create', 'vehicle.update', 'vehicle.tyre-map', 'vehicle.odometer.update',
            'fleet.manage',
            'trailer.assign', 'trailer.transfer',
            'movement.create', 'movement.check', 'movement.approve', 'movement.reject',
            'disposal.create', 'disposal.check', 'disposal.approve', 'disposal.reject',
            'report.view', 'report.export',
            'audit.view', 'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $rolePermissions = [
            'Super Admin' => $permissions,
            'Admin' => $permissions,
            'Store Keeper' => [
                'tyre.view', 'tyre.create', 'tyre.update',
                'vehicle.view', 'vehicle.tyre-map', 'vehicle.odometer.update',
                'movement.create', 'disposal.create',
                'trailer.assign', 'trailer.transfer',
                'report.view',
            ],
            'Store Manager' => [
                'tyre.view', 'tyre.create', 'tyre.update',
                'vehicle.view', 'vehicle.update', 'vehicle.odometer.update', 'movement.create', 'movement.check',
                'fleet.manage',
                'trailer.assign', 'trailer.transfer',
                'disposal.create', 'disposal.check', 'report.view', 'report.export',
            ],
            'Company Manager' => [
                'tyre.view', 'tyre.approve',
                'vehicle.view', 'vehicle.tyre-map',
                'movement.approve', 'movement.reject',
                'disposal.approve', 'disposal.reject',
                'report.view', 'report.export',
                'audit.view',
            ],
            'Technic Clerk' => [
                'tyre.view', 'vehicle.view', 'vehicle.tyre-map',
                'movement.create', 'report.view',
            ],
            'Technic and Maintenance Head' => [
                'tyre.view', 'vehicle.view', 'vehicle.tyre-map',
                'vehicle.odometer.update',
                'movement.check', 'movement.reject',
                'disposal.check', 'disposal.reject',
                'report.view', 'report.export',
            ],
            'Auditor' => [
                'tyre.view', 'vehicle.view', 'movement.check', 'disposal.check',
                'audit.view', 'report.view', 'report.export',
            ],
            'Management Viewer' => [
                'tyre.view', 'vehicle.view', 'report.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::findOrCreate($roleName);
            $role->syncPermissions($perms);
        }

        if (! $this->shouldSeedDemoUsers()) {
            $this->command?->warn('Default TMS users were not seeded. Set TMS_SEED_DEMO_USERS=true and TMS_DEMO_USER_PASSWORD to opt in.');

            return;
        }

        $this->seedUser('admin@menkem.com', 'TMS Super Admin', 'Super Admin');
        $this->seedUser('store@menkem.com', 'Store Manager', 'Store Manager');
        $this->seedUser('storekeeper@menkem.com', 'Store Keeper', 'Store Keeper');
        $this->migrateTechnicalHeadAccount();
        $this->seedUser('demmelash.fetene@menkemintl.com', 'Demmelash Fetene', 'Technic and Maintenance Head');
        $this->seedUser('manager@menkem.com', 'Company Manager', 'Company Manager');
    }

    private function seedUser(string $email, string $name, string $role): void
    {
        $password = config('tms.demo_user_password');

        if (! is_string($password) || $password === '') {
            if (! app()->environment(['local', 'testing'])) {
                throw new \RuntimeException('TMS_DEMO_USER_PASSWORD is required when demo user seeding is enabled in production.');
            }

            $password = 'password';
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_active' => true,
            ],
        );

        $user->forceFill(['name' => $name])->save();
        $user->syncRoles([$role]);
    }

    private function shouldSeedDemoUsers(): bool
    {
        return app()->environment(['local', 'testing']) || config('tms.seed_demo_users');
    }

    private function migrateTechnicalHeadAccount(): void
    {
        $targetEmail = 'demmelash.fetene@menkemintl.com';

        if (User::query()->where('email', $targetEmail)->exists()) {
            return;
        }

        User::query()
            ->where('email', 'technical.head@menkem.com')
            ->update(['email' => $targetEmail, 'name' => 'Demmelash Fetene']);
    }
}
