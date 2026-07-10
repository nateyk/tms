<?php

namespace App\Services;

use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Enums\VoucherStatus;
use App\Exceptions\TyreBusinessException;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreDisposal;
use Illuminate\Support\Facades\DB;

class TyreDisposalService
{
    public function __construct(
        protected TyreAssignmentService $assignmentService,
        protected VoucherNumberGenerator $numberGenerator,
    ) {}

    public function complete(TyreDisposal $disposal, int $approvedBy): TyreDisposal
    {
        return DB::transaction(function () use ($disposal, $approvedBy) {
            $tyre = Tyre::query()->whereKey($disposal->tyre_id)->lockForUpdate()->firstOrFail();

            if ($tyre->isDisposed()) {
                throw new TyreBusinessException('Tyre is already disposed.');
            }

            TyreAssignment::query()
                ->where('tyre_id', $tyre->id)
                ->where('status', TyreAssignmentStatus::Active)
                ->update([
                    'status' => TyreAssignmentStatus::Removed,
                    'removed_date' => now()->toDateString(),
                    'removed_by' => $approvedBy,
                ]);

            $tyre->update([
                'status' => TyreStatus::Disposed,
                'current_location_type' => TyreLocationType::DisposalYard,
                'current_location_id' => null,
                'current_position_code' => null,
            ]);

            $disposal->update([
                'status' => VoucherStatus::Completed,
                'approved_by' => $approvedBy,
                'completed_at' => now(),
            ]);

            return $disposal->fresh();
        });
    }

    public function createDraft(array $data, int $preparedBy): TyreDisposal
    {
        $tyre = Tyre::query()->findOrFail($data['tyre_id']);

        if ($tyre->isDisposed()) {
            throw new TyreBusinessException('Tyre is already disposed.');
        }

        return TyreDisposal::query()->create(array_merge($data, [
            'disposal_no' => $this->numberGenerator->generate('DSP', new TyreDisposal, 'disposal_no'),
            'status' => VoucherStatus::Draft,
            'prepared_by' => $preparedBy,
            'last_location_type' => $tyre->current_location_type,
            'last_location_id' => $tyre->current_location_id,
            'last_position_code' => $tyre->current_position_code,
        ]));
    }
}
