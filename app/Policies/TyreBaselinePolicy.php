<?php

namespace App\Policies;

use App\Models\TyreBaseline;
use App\Models\User;

class TyreBaselinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tyre.view');
    }

    public function view(User $user, TyreBaseline $baseline): bool
    {
        return $user->can('tyre.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tyre.create');
    }

    public function update(User $user, TyreBaseline $baseline): bool
    {
        return $user->can('tyre.update');
    }

    public function delete(User $user, TyreBaseline $baseline): bool
    {
        return $user->can('tyre.delete');
    }
}
