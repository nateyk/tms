<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::query()
            ->with('permissions:id,name')
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
                'permissions' => $role->permissions->pluck('name')->sort()->values(),
            ]);

        $allPermissions = $roles
            ->flatMap(fn (array $role) => $role['permissions'])
            ->unique()
            ->sort()
            ->values();

        return Inertia::render('admin/roles/index', [
            'roles' => $roles,
            'allPermissions' => $allPermissions,
        ]);
    }
}
