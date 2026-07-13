<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
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
                'vehicle.view', 'vehicle.odometer.update', 'movement.create',
                'report.view',
            ],
            'Store Manager' => [
                'tyre.view', 'tyre.create', 'tyre.update',
                'vehicle.view', 'vehicle.update', 'vehicle.odometer.update', 'movement.create', 'movement.check',
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
                'tyre.view', 'tyre.update', 'vehicle.view', 'vehicle.tyre-map', 'vehicle.update',
                'movement.create', 'movement.check', 'movement.approve',
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

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@menkem.com'],
            [
                'name' => 'TMS Super Admin',
                'password' => 'password',
            ]
        );

        $admin->syncRoles(['Super Admin']);

        $storeManager = User::query()->firstOrCreate(
            ['email' => 'store@menkem.com'],
            [
                'name' => 'Store Manager',
                'password' => 'password',
            ]
        );

        $storeManager->syncRoles(['Store Manager']);

        $companyManager = User::query()->firstOrCreate(
            ['email' => 'manager@menkem.com'],
            [
                'name' => 'Company Manager',
                'password' => 'password',
            ]
        );

        $companyManager->syncRoles(['Company Manager']);
    }
}
