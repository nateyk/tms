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
        return $user->can('movement.create')
            && $tyreMovement->status === VoucherStatus::Draft
            && $user->id === $tyreMovement->prepared_by;
    }

    public function delete(User $user, TyreMovement $tyreMovement): bool
    {
        return false;
    }

    public function submit(User $user, TyreMovement $tyreMovement): bool
    {
        return $user->can('movement.create')
            && $tyreMovement->status === VoucherStatus::Draft
            && $user->id === $tyreMovement->prepared_by;
    }

    public function check(User $user, TyreMovement $tyreMovement): bool
    {
        return $user->can('movement.check')
            && $tyreMovement->status === VoucherStatus::Submitted
            && $user->id !== $tyreMovement->prepared_by;
    }

    public function approve(User $user, TyreMovement $tyreMovement): bool
    {
        return $user->can('movement.approve')
            && $tyreMovement->status === VoucherStatus::Checked
            && $user->id !== $tyreMovement->prepared_by
            && $user->id !== $tyreMovement->checked_by;
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
        if ($tyreMovement->status->isTerminal()) {
            return false;
        }

        return ($tyreMovement->status === VoucherStatus::Draft
                && $user->id === $tyreMovement->prepared_by
                && $user->can('movement.create'))
            || $user->can('movement.reject')
            || $user->can('movement.approve');
    }
}
