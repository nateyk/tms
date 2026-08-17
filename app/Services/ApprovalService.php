<?php

namespace App\Services;

use App\Enums\VoucherStatus;
use App\Exceptions\TyreBusinessException;
use App\Models\TrailerTransfer;
use App\Models\TyreDisposal;
use App\Models\TyreMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public function submit(Model $voucher): Model
    {
        return DB::transaction(function () use ($voucher): Model {
            $voucher = $this->lockedVoucher($voucher);
            $this->assertStatus($voucher, VoucherStatus::Draft);
            $this->assertPreparedByCurrentUser($voucher);

            $voucher->update([
                'status' => VoucherStatus::Submitted,
                'submitted_at' => now(),
            ]);

            return $voucher->fresh();
        });
    }

    public function check(Model $voucher): Model
    {
        return DB::transaction(function () use ($voucher): Model {
            $voucher = $this->lockedVoucher($voucher);
            $this->assertStatus($voucher, VoucherStatus::Submitted);
            $actorId = $this->actorId();

            if ((int) $voucher->prepared_by === $actorId) {
                throw new TyreBusinessException('The voucher preparer cannot check their own voucher.');
            }

            $voucher->update([
                'status' => VoucherStatus::Checked,
                'checked_by' => $actorId,
                'checked_at' => now(),
            ]);

            return $voucher->fresh();
        });
    }

    public function approve(Model $voucher): Model
    {
        return DB::transaction(function () use ($voucher): Model {
            $voucher = $this->lockedVoucher($voucher);
            $this->assertStatus($voucher, VoucherStatus::Checked);
            $actorId = $this->actorId();

            if (in_array($actorId, [(int) $voucher->prepared_by, (int) $voucher->checked_by], true)) {
                throw new TyreBusinessException('The approver must be different from both the preparer and checker.');
            }

            $voucher->update([
                'status' => VoucherStatus::Approved,
                'approved_by' => $actorId,
                'approved_at' => now(),
            ]);

            return $voucher->fresh();
        });
    }

    public function reject(Model $voucher, ?string $reason = null): Model
    {
        return DB::transaction(function () use ($voucher, $reason): Model {
            $voucher = $this->lockedVoucher($voucher);

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
        });
    }

    public function cancel(Model $voucher, ?string $reason = null): Model
    {
        return DB::transaction(function () use ($voucher, $reason): Model {
            $voucher = $this->lockedVoucher($voucher);

            if ($voucher->status->isTerminal()) {
                throw new TyreBusinessException('Cannot void a terminal voucher.');
            }

            $voucher->update([
                'status' => VoucherStatus::Cancelled,
                'voided_by' => $this->actorId(),
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            return $voucher->fresh();
        });
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

    private function actorId(): int
    {
        $actorId = Auth::id();

        if (! $actorId) {
            throw new TyreBusinessException('An authenticated user is required for voucher workflow actions.');
        }

        return (int) $actorId;
    }

    private function assertPreparedByCurrentUser(Model $voucher): void
    {
        if ((int) $voucher->prepared_by !== $this->actorId()) {
            throw new TyreBusinessException('Only the voucher preparer can submit this draft.');
        }
    }

    private function lockedVoucher(Model $voucher): Model
    {
        return $voucher->newQuery()
            ->whereKey($voucher->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }
}
