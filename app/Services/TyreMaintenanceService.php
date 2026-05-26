<?php

namespace App\Services;

use App\Enums\MaintenanceStatus;
use App\Enums\TyreStatus;
use App\Exceptions\TyreBusinessException;
use App\Models\Tyre;
use App\Models\TyreMaintenance;
use Illuminate\Support\Facades\DB;

class TyreMaintenanceService
{
    public function __construct(
        protected VoucherNumberGenerator $numberGenerator,
    ) {}

    public function submit(TyreMaintenance $maintenance): TyreMaintenance
    {
        if ($maintenance->status !== MaintenanceStatus::Draft) {
            throw new TyreBusinessException('Only draft maintenance can be submitted.');
        }

        $maintenance->update(['status' => MaintenanceStatus::Submitted]);

        return $maintenance->fresh();
    }

    public function approve(TyreMaintenance $maintenance, int $approvedBy): TyreMaintenance
    {
        if ($maintenance->status !== MaintenanceStatus::Submitted) {
            throw new TyreBusinessException('Maintenance must be submitted before approval.');
        }

        $maintenance->update([
            'status' => MaintenanceStatus::Approved,
            'approved_by' => $approvedBy,
        ]);

        return $maintenance->fresh();
    }

    public function reject(TyreMaintenance $maintenance, ?string $reason = null): TyreMaintenance
    {
        if ($maintenance->status->value === MaintenanceStatus::Completed->value) {
            throw new TyreBusinessException('Completed maintenance cannot be rejected.');
        }

        $notes = $maintenance->notes;
        if ($reason) {
            $notes = trim(($notes ?? '')."\n[Rejected] ".$reason);
        }

        $maintenance->update([
            'status' => MaintenanceStatus::Rejected,
            'notes' => $notes,
        ]);

        return $maintenance->fresh();
    }

    public function createDraft(array $data, int $preparedBy): TyreMaintenance
    {
        $tyre = Tyre::query()->findOrFail($data['tyre_id']);

        if ($tyre->isDisposed()) {
            throw new TyreBusinessException('Disposed tyres cannot be maintained.');
        }

        return TyreMaintenance::query()->create(array_merge($data, [
            'maintenance_no' => $this->numberGenerator->generate('MNT', new TyreMaintenance, 'maintenance_no'),
            'status' => MaintenanceStatus::Draft,
            'prepared_by' => $preparedBy,
        ]));
    }

    public function start(TyreMaintenance $maintenance): TyreMaintenance
    {
        if ($maintenance->status !== MaintenanceStatus::Approved) {
            throw new TyreBusinessException('Maintenance must be approved before starting.');
        }

        $maintenance->update(['status' => MaintenanceStatus::InProgress]);
        $maintenance->tyre->update(['status' => TyreStatus::Maintenance]);

        return $maintenance->fresh();
    }

    public function complete(TyreMaintenance $maintenance, int $completedBy): TyreMaintenance
    {
        if ($maintenance->status !== MaintenanceStatus::InProgress) {
            throw new TyreBusinessException('Maintenance must be in progress before completion.');
        }

        return DB::transaction(function () use ($maintenance, $completedBy) {
            $tyre = Tyre::query()->whereKey($maintenance->tyre_id)->lockForUpdate()->firstOrFail();

            $maintenance->update([
                'status' => MaintenanceStatus::Completed,
                'approved_by' => $completedBy,
            ]);

            if (! $tyre->isDisposed()) {
                $restoredStatus = $tyre->activeAssignment()->exists()
                    ? TyreStatus::Active
                    : TyreStatus::Available;
                $tyre->update(['status' => $restoredStatus]);
            }

            return $maintenance->fresh();
        });
    }
}
