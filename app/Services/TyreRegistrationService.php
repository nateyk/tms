<?php

namespace App\Services;

use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Exceptions\TyreBusinessException;
use App\Models\Store;
use App\Models\Tyre;
use Illuminate\Support\Facades\DB;

class TyreRegistrationService
{
    public function __construct(
        protected TyreQrCodeService $qrCodeService,
    ) {}

    public function register(array $data): Tyre
    {
        $store = Store::query()->where('is_default', true)->first()
            ?? Store::query()->first();

        return Tyre::query()->create(array_merge($data, [
            'current_location_type' => TyreLocationType::Store,
            'current_location_id' => $store?->id,
            'current_position_code' => null,
            'status' => TyreStatus::PendingApproval,
        ]));
    }

    public function approve(Tyre $tyre, int $approvedBy): Tyre
    {
        return DB::transaction(function () use ($tyre, $approvedBy) {
            if ($tyre->status !== TyreStatus::PendingApproval) {
                throw new TyreBusinessException('Only pending tyres can be approved for registration.');
            }

            if ($tyre->isDisposed()) {
                throw new TyreBusinessException('Disposed tyres cannot be registered.');
            }

            $tyre->update(['status' => TyreStatus::Available]);

            $this->qrCodeService->generateForTyre($tyre->fresh());

            activity()
                ->performedOn($tyre)
                ->withProperties(['approved_by' => $approvedBy])
                ->log('Tyre registration approved');

            return $tyre->fresh();
        });
    }
}
