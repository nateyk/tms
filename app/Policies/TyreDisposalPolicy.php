<?php

namespace App\Policies;

use App\Enums\VoucherStatus;
use App\Models\TyreDisposal;
use App\Models\User;

class TyreDisposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('disposal.create')
            || $user->can('disposal.check')
            || $user->can('disposal.approve');
    }

    public function view(User $user, TyreDisposal $disposal): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('disposal.create');
    }

    public function update(User $user, TyreDisposal $disposal): bool
    {
        return $user->can('disposal.create') && $disposal->status === VoucherStatus::Draft;
    }

    public function delete(User $user, TyreDisposal $disposal): bool
    {
        return $user->can('disposal.create') && $disposal->status === VoucherStatus::Draft;
    }

    public function submit(User $user, TyreDisposal $disposal): bool
    {
        return $user->can('disposal.create') && $disposal->status === VoucherStatus::Draft;
    }

    public function check(User $user, TyreDisposal $disposal): bool
    {
        return $user->can('disposal.check') && $disposal->status === VoucherStatus::Submitted;
    }

    public function approve(User $user, TyreDisposal $disposal): bool
    {
        return $user->can('disposal.approve')
            && in_array($disposal->status, [VoucherStatus::Submitted, VoucherStatus::Checked], true);
    }

    public function reject(User $user, TyreDisposal $disposal): bool
    {
        return $user->can('disposal.reject') && ! $disposal->status->isTerminal();
    }

    public function complete(User $user, TyreDisposal $disposal): bool
    {
        return $user->can('disposal.approve') && $disposal->status === VoucherStatus::Approved;
    }

    public function cancel(User $user, TyreDisposal $disposal): bool
    {
        return $user->can('disposal.create') && $disposal->status === VoucherStatus::Draft;
    }
}
