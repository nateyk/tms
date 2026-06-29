<?php

namespace App\Policies;

use App\Enums\VoucherStatus;
use App\Models\TyreMovement;
use App\Models\User;

class TyreMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('movement.create') || $user->can('movement.check') || $user->can('movement.approve');
    }

    public function view(User $user, TyreMovement $tyreMovement): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('movement.create');
    }

    public function update(User $user, TyreMovement $tyreMovement): bool
    {
        return $user->can('movement.create') && $tyreMovement->status === VoucherStatus::Draft;
    }

    public function delete(User $user, TyreMovement $tyreMovement): bool
    {
        return $user->can('movement.create')
            && $tyreMovement->status === VoucherStatus::Draft;
    }

    public function submit(User $user, TyreMovement $tyreMovement): bool
    {
        return $user->can('movement.create')
            && $tyreMovement->status === VoucherStatus::Draft;
    }

    public function check(User $user, TyreMovement $tyreMovement): bool
    {
        return $user->can('movement.check')
            && $tyreMovement->status === VoucherStatus::Submitted;
    }

    public function approve(User $user, TyreMovement $tyreMovement): bool
    {
        return $user->can('movement.approve')
            && in_array($tyreMovement->status, [VoucherStatus::Submitted, VoucherStatus::Checked], true);
    }

    public function reject(User $user, TyreMovement $tyreMovement): bool
    {
        return $user->can('movement.reject')
            && ! $tyreMovement->status->isTerminal();
    }

    public function complete(User $user, TyreMovement $tyreMovement): bool
    {
        return $user->can('movement.approve')
            && $tyreMovement->status === VoucherStatus::Approved;
    }

    public function cancel(User $user, TyreMovement $tyreMovement): bool
    {
        return $user->can('movement.create')
            && $tyreMovement->status === VoucherStatus::Draft;
    }
}
