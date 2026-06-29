<?php

namespace App\Services;

use App\Enums\AssetType;
use App\Enums\CombinationStatus;
use App\Enums\VoucherStatus;
use App\Exceptions\TyreBusinessException;
use App\Models\SystemSetting;
use App\Models\TrailerTransfer;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use Illuminate\Support\Facades\DB;

class TrailerTransferService
{
    public function __construct(
        protected VoucherNumberGenerator $numberGenerator,
    ) {}

    public function complete(TrailerTransfer $transfer, int $approvedBy): TrailerTransfer
    {
        return DB::transaction(function () use ($transfer, $approvedBy) {
            $trailer = Vehicle::query()
                ->whereKey($transfer->trailer_vehicle_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($trailer->asset_type !== AssetType::Trailer) {
                throw new TyreBusinessException('Selected vehicle is not a trailer.');
            }

            $toPower = Vehicle::query()
                ->whereKey($transfer->to_power_vehicle_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($toPower->asset_type !== AssetType::PowerVehicle) {
                throw new TyreBusinessException('Destination must be a power vehicle.');
            }

            $maxTrailers = (int) SystemSetting::get('max_trailers_per_power', 1);
            $activeOnPower = VehicleCombination::query()
                ->where('power_vehicle_id', $toPower->id)
                ->where('status', CombinationStatus::Active)
                ->where('trailer_vehicle_id', '!=', $trailer->id)
                ->count();

            if ($activeOnPower >= $maxTrailers) {
                throw new TyreBusinessException(
                    "Power unit {$toPower->vehicle_code} already has the maximum of {$maxTrailers} active trailer(s)."
                );
            }

            VehicleCombination::query()
                ->where('trailer_vehicle_id', $trailer->id)
                ->where('status', CombinationStatus::Active)
                ->update([
                    'status' => CombinationStatus::Detached,
                    'detached_date' => $transfer->transfer_date,
                    'odometer_at_detach' => $transfer->from_odometer,
                    'detached_by' => $approvedBy,
                ]);

            VehicleCombination::query()->create([
                'power_vehicle_id' => $toPower->id,
                'trailer_vehicle_id' => $trailer->id,
                'attached_date' => $transfer->transfer_date,
                'odometer_at_attach' => $transfer->to_odometer,
                'status' => CombinationStatus::Active,
                'attached_by' => $approvedBy,
                'approved_by' => $approvedBy,
            ]);

            $transfer->update([
                'status' => VoucherStatus::Completed,
                'approved_by' => $approvedBy,
                'completed_at' => now(),
            ]);

            activity()
                ->performedOn($transfer)
                ->withProperties(['approved_by' => $approvedBy])
                ->log('Trailer transfer completed');

            return $transfer->fresh();
        });
    }

    public function createDraft(array $data, int $preparedBy): TrailerTransfer
    {
        return TrailerTransfer::query()->create(array_merge($data, [
            'transfer_no' => $this->numberGenerator->generate('TRF', new TrailerTransfer, 'transfer_no'),
            'status' => VoucherStatus::Draft,
            'prepared_by' => $preparedBy,
        ]));
    }
}
