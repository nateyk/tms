<?php

namespace App\Services;

use App\Enums\AssignmentAssetType;
use App\Enums\MovementType;
use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Enums\VoucherStatus;
use App\Exceptions\TyreBusinessException;
use App\Models\Tyre;
use App\Models\TyreMovement;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class TyreMovementService
{
    public function __construct(
        protected TyreAssignmentService $assignmentService,
        protected VoucherNumberGenerator $numberGenerator,
    ) {}

    public function assertCanCreateMovement(Tyre $tyre): void
    {
        if ($tyre->isDisposed()) {
            throw new TyreBusinessException('Disposed tyres cannot be moved.');
        }

        $pending = TyreMovement::query()
            ->where('tyre_id', $tyre->id)
            ->whereIn('status', [
                VoucherStatus::Draft,
                VoucherStatus::Submitted,
                VoucherStatus::Checked,
                VoucherStatus::Approved,
            ])
            ->exists();

        if ($pending) {
            throw new TyreBusinessException('Tyre has a pending movement voucher.');
        }
    }

    public function validateSourceAtApproval(TyreMovement $movement, Tyre $tyre): void
    {
        if ($movement->from_location_type && $tyre->current_location_type !== $movement->from_location_type) {
            throw new TyreBusinessException('Tyre current location does not match movement source.');
        }

        if ($movement->from_location_id && (int) $tyre->current_location_id !== (int) $movement->from_location_id) {
            throw new TyreBusinessException('Tyre current location ID does not match movement source.');
        }

        if ($movement->from_position_code && $tyre->current_position_code !== $movement->from_position_code) {
            throw new TyreBusinessException('Tyre current position does not match movement source.');
        }
    }

    public function validateDestinationAtApproval(TyreMovement $movement): void
    {
        if (! $movement->to_position_code || ! $movement->to_location_id) {
            return;
        }

        $assetType = match ($movement->to_location_type) {
            TyreLocationType::PowerVehicle => AssignmentAssetType::PowerVehicle,
            TyreLocationType::Trailer => AssignmentAssetType::Trailer,
            default => null,
        };

        if ($assetType) {
            $this->assignmentService->assertPositionEmpty(
                $assetType,
                (int) $movement->to_location_id,
                $movement->to_position_code
            );
        }
    }

    public function complete(TyreMovement $movement, int $approvedBy): TyreMovement
    {
        return DB::transaction(function () use ($movement, $approvedBy) {
            $tyre = Tyre::query()->whereKey($movement->tyre_id)->lockForUpdate()->firstOrFail();

            if ($tyre->isDisposed()) {
                throw new TyreBusinessException('Cannot complete movement for disposed tyre.');
            }

            $this->validateSourceAtApproval($movement, $tyre);
            $this->validateDestinationAtApproval($movement);

            $removedOdometer = $movement->from_odometer ?? $movement->to_odometer;

            $this->assignmentService->closeActiveAssignment(
                $tyre,
                $removedOdometer,
                $approvedBy,
                $movement->id
            );

            [$locationType, $locationId, $position, $status, $assetType] = $this->resolveDestination($movement);

            if ($assetType && $locationId && $position) {
                $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($locationId);
                $this->assignmentService->createActiveAssignment(
                    $tyre,
                    $assetType,
                    $vehicle,
                    $position,
                    $movement->to_odometer,
                    $approvedBy,
                    $movement->id
                );
            }

            $this->assignmentService->updateTyreLocation($tyre, $locationType, $locationId, $position, $status);

            $movement->update([
                'status' => VoucherStatus::Completed,
                'approved_by' => $approvedBy,
                'approved_at' => now(),
                'completed_at' => now(),
            ]);

            activity()
                ->performedOn($movement)
                ->withProperties(['approved_by' => $approvedBy])
                ->log('Tyre movement completed');

            return $movement->fresh();
        });
    }

    /**
     * @return array{0: TyreLocationType, 1: ?int, 2: ?string, 3: TyreStatus, 4: ?AssignmentAssetType}
     */
    protected function resolveDestination(TyreMovement $movement): array
    {
        return match ($movement->to_location_type) {
            TyreLocationType::Store => [
                TyreLocationType::Store,
                $movement->to_location_id,
                null,
                TyreStatus::Available,
                null,
            ],
            TyreLocationType::PowerVehicle => [
                TyreLocationType::PowerVehicle,
                $movement->to_location_id,
                $movement->to_position_code,
                TyreStatus::Active,
                AssignmentAssetType::PowerVehicle,
            ],
            TyreLocationType::Trailer => [
                TyreLocationType::Trailer,
                $movement->to_location_id,
                $movement->to_position_code,
                TyreStatus::Active,
                AssignmentAssetType::Trailer,
            ],
            TyreLocationType::MaintenanceCenter => [
                TyreLocationType::MaintenanceCenter,
                $movement->to_location_id,
                $movement->to_position_code,
                TyreStatus::Maintenance,
                null,
            ],
            TyreLocationType::DisposalYard => [
                TyreLocationType::DisposalYard,
                $movement->to_location_id,
                null,
                TyreStatus::Disposed,
                null,
            ],
            default => throw new TyreBusinessException('Invalid destination location type.'),
        };
    }

    public function createDraft(array $data, int $preparedBy): TyreMovement
    {
        $tyre = Tyre::query()->findOrFail($data['tyre_id']);
        $this->assertCanCreateMovement($tyre);

        $toLocationType = $this->normalizeLocationType($data['to_location_type'] ?? null);
        $data['movement_type'] = $this->deriveMovementType(
            $tyre->current_location_type,
            $tyre->current_location_id,
            $toLocationType,
            isset($data['to_location_id']) ? (int) $data['to_location_id'] : null,
        );

        return TyreMovement::query()->create(array_merge($data, [
            'movement_no' => $this->numberGenerator->generate('MOV', new TyreMovement, 'movement_no'),
            'status' => VoucherStatus::Draft,
            'prepared_by' => $preparedBy,
            'from_location_type' => $tyre->current_location_type,
            'from_location_id' => $tyre->current_location_id,
            'from_position_code' => $tyre->current_position_code,
        ]));
    }

    protected function deriveMovementType(
        ?TyreLocationType $fromType,
        ?int $fromId,
        ?TyreLocationType $toType,
        ?int $toId,
    ): MovementType {
        return match (true) {
            $fromType === TyreLocationType::Store && $this->isVehicleLocation($toType) => MovementType::StoreToVehicle,
            $fromType === TyreLocationType::Store && $toType === TyreLocationType::MaintenanceCenter => MovementType::StoreToMaintenance,
            $fromType === TyreLocationType::MaintenanceCenter && $toType === TyreLocationType::Store => MovementType::MaintenanceToStore,
            $fromType === TyreLocationType::MaintenanceCenter && $this->isVehicleLocation($toType) => MovementType::MaintenanceToVehicle,
            $this->isVehicleLocation($fromType) && $toType === TyreLocationType::Store => MovementType::VehicleToStore,
            $this->isVehicleLocation($fromType) && $toType === TyreLocationType::MaintenanceCenter => MovementType::VehicleToMaintenance,
            $this->isVehicleLocation($fromType) && $this->isVehicleLocation($toType) && $fromType === $toType && $fromId === $toId => MovementType::PositionChangeSameAsset,
            $fromType === TyreLocationType::PowerVehicle && $toType === TyreLocationType::Trailer => MovementType::PowerToTrailer,
            $fromType === TyreLocationType::Trailer && $toType === TyreLocationType::PowerVehicle => MovementType::TrailerToPower,
            $this->isVehicleLocation($fromType) && $this->isVehicleLocation($toType) => MovementType::VehicleToVehicle,
            default => MovementType::VehicleToVehicle,
        };
    }

    protected function normalizeLocationType(mixed $locationType): ?TyreLocationType
    {
        return $locationType instanceof TyreLocationType
            ? $locationType
            : TyreLocationType::tryFrom((string) $locationType);
    }

    protected function isVehicleLocation(?TyreLocationType $locationType): bool
    {
        return in_array($locationType, [TyreLocationType::PowerVehicle, TyreLocationType::Trailer], true);
    }
}
