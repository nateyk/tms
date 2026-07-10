<?php

namespace App\Policies;

use App\Enums\VoucherStatus;
use App\Models\TrailerTransfer;
use App\Models\User;

class TrailerTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('trailer.transfer') || $user->can('movement.check') || $user->can('movement.approve');
    }

    public function view(User $user, TrailerTransfer $transfer): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('trailer.transfer');
    }

    public function update(User $user, TrailerTransfer $transfer): bool
    {
        return $user->can('trailer.transfer') && $transfer->status === VoucherStatus::Draft;
    }

    public function delete(User $user, TrailerTransfer $transfer): bool
    {
        return $user->can('trailer.transfer') && $transfer->status === VoucherStatus::Draft;
    }

    public function submit(User $user, TrailerTransfer $transfer): bool
    {
        return $user->can('trailer.transfer') && $transfer->status === VoucherStatus::Draft;
    }

    public function check(User $user, TrailerTransfer $transfer): bool
    {
        return $user->can('movement.check') && $transfer->status === VoucherStatus::Submitted;
    }

    public function approve(User $user, TrailerTransfer $transfer): bool
    {
        return $user->can('movement.approve')
            && in_array($transfer->status, [VoucherStatus::Submitted, VoucherStatus::Checked], true);
    }

    public function reject(User $user, TrailerTransfer $transfer): bool
    {
        return $user->can('movement.reject') && ! $transfer->status->isTerminal();
    }

    public function complete(User $user, TrailerTransfer $transfer): bool
    {
        return $user->can('movement.approve') && $transfer->status === VoucherStatus::Approved;
    }

    public function cancel(User $user, TrailerTransfer $transfer): bool
    {
        return $user->can('trailer.transfer') && $transfer->status === VoucherStatus::Draft;
    }
}
