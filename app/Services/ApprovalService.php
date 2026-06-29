<?php

namespace App\Services;

use App\Enums\VoucherStatus;
use App\Exceptions\TyreBusinessException;
use App\Models\TrailerTransfer;
use App\Models\TyreDisposal;
use App\Models\TyreMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ApprovalService
{
    public function submit(Model $voucher): Model
    {
        $this->assertStatus($voucher, VoucherStatus::Draft);

        $voucher->update([
            'status' => VoucherStatus::Submitted,
            'submitted_at' => now(),
        ]);

        return $voucher->fresh();
    }

    public function check(Model $voucher): Model
    {
        $this->assertStatus($voucher, VoucherStatus::Submitted);

        $voucher->update([
            'status' => VoucherStatus::Checked,
            'checked_by' => Auth::id(),
            'checked_at' => now(),
        ]);

        return $voucher->fresh();
    }

    public function approve(Model $voucher): Model
    {
        $current = $voucher->status;

        if (! in_array($current, [VoucherStatus::Submitted, VoucherStatus::Checked], true)) {
            $currentValue = $current instanceof VoucherStatus ? $current->value : (string) $current;

            throw new TyreBusinessException(
                "Invalid status transition. Expected [submitted] or [checked], got [{$currentValue}]."
            );
        }

        $update = [
            'status' => VoucherStatus::Approved,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ];

        if ($current === VoucherStatus::Submitted) {
            $update['checked_by'] = Auth::id();
            $update['checked_at'] = now();
        }

        $voucher->update($update);

        return $voucher->fresh();
    }

    public function reject(Model $voucher, ?string $reason = null): Model
    {
        if ($voucher->status->isTerminal()) {
            throw new TyreBusinessException('Cannot reject a terminal voucher.');
        }

        $notes = $voucher->notes;
        if ($reason) {
            $notes = trim(($notes ?? '')."\n[Rejected] ".$reason);
        }

        $voucher->update([
            'status' => VoucherStatus::Rejected,
            'notes' => $notes,
        ]);

        return $voucher->fresh();
    }

    public function cancel(Model $voucher): Model
    {
        $this->assertStatus($voucher, VoucherStatus::Draft);

        $voucher->update([
            'status' => VoucherStatus::Cancelled,
        ]);

        return $voucher->fresh();
    }

    public function completeMovement(TyreMovement $movement): TyreMovement
    {
        $this->assertStatus($movement, VoucherStatus::Approved);

        return app(TyreMovementService::class)->complete($movement, (int) Auth::id());
    }

    public function completeTrailerTransfer(TrailerTransfer $transfer): TrailerTransfer
    {
        $this->assertStatus($transfer, VoucherStatus::Approved);

        return app(TrailerTransferService::class)->complete($transfer, (int) Auth::id());
    }

    public function completeDisposal(TyreDisposal $disposal): TyreDisposal
    {
        $this->assertStatus($disposal, VoucherStatus::Approved);

        return app(TyreDisposalService::class)->complete($disposal, (int) Auth::id());
    }

    protected function assertStatus(Model $voucher, VoucherStatus $expected): void
    {
        $current = $voucher->status;

        if ($current instanceof VoucherStatus && $current !== $expected) {
            throw new TyreBusinessException(
                "Invalid status transition. Expected [{$expected->value}], got [{$current->value}]."
            );
        }
    }
}
