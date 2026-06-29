<?php

namespace App\Policies;

use App\Models\Tyre;
use App\Models\User;

class TyrePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tyre.view');
    }

    public function view(User $user, Tyre $tyre): bool
    {
        return $user->can('tyre.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tyre.create');
    }

    public function update(User $user, Tyre $tyre): bool
    {
        return $user->can('tyre.update');
    }

    public function delete(User $user, Tyre $tyre): bool
    {
        return $user->can('tyre.delete') && ! $tyre->isDisposed();
    }

    public function approve(User $user, Tyre $tyre): bool
    {
        return $user->can('tyre.approve');
    }
}
