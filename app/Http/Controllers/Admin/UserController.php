<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->with('roles:id,name')
            ->orderBy('name')
            ->paginate(15)
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at,
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/users/create', [
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $roles = $request->validated('roles');
        $user = DB::transaction(function () use ($request, $roles): User {
            $user = User::query()->create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => Hash::make($request->validated('password')),
                'is_active' => $request->boolean('is_active', true),
            ]);

            $user->syncRoles($roles);

            return $user;
        });

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties(['roles' => $roles, 'is_active' => $user->is_active])
            ->log('User account created');

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('name'),
            ],
            'roles' => $this->roleOptions(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $roles = $request->validated('roles');
        $isActive = $request->boolean('is_active');

        if ($user->is($request->user()) && ! $isActive) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if ($this->wouldRemoveLastActiveAdministrator($user, $roles, $isActive)) {
            return back()->with('error', 'At least one active Super Admin or Admin account must remain.');
        }

        $previousRoles = $user->roles->pluck('name')->all();
        $wasActive = $user->is_active;

        $user = DB::transaction(function () use ($request, $user, $roles, $isActive): User {
            $user = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $user->update([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'is_active' => $isActive,
            ]);

            if ($password = $request->validated('password')) {
                $user->update(['password' => Hash::make($password)]);
            }

            $user->syncRoles($roles);

            return $user;
        });

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->withProperties([
                'roles_before' => $previousRoles,
                'roles_after' => $roles,
                'active_before' => $wasActive,
                'active_after' => $isActive,
                'password_changed' => filled($request->validated('password')),
            ])
            ->log('User account updated');

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        return back()->with('error', 'User accounts are retained for audit history. Deactivate the account instead.');
    }

    /** @return list<string> */
    private function roleOptions(): array
    {
        return Role::query()->orderBy('name')->pluck('name')->all();
    }

    /** @param list<string> $newRoles */
    private function wouldRemoveLastActiveAdministrator(User $user, array $newRoles, bool $isActive): bool
    {
        $administratorRoles = ['Super Admin', 'Admin'];

        if (! $user->hasAnyRole($administratorRoles)) {
            return false;
        }

        if ($isActive && array_intersect($administratorRoles, $newRoles) !== []) {
            return false;
        }

        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $administratorRoles))
            ->get(['id'])
            ->count() <= 1;
    }
}
