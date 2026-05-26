<?php

namespace App\Services;

use App\Enums\AssignmentAssetType;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Exceptions\TyreBusinessException;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\Vehicle;

class TyreAssignmentService
{
    public function assertPositionEmpty(AssignmentAssetType $assetType, int $assetId, string $positionCode): void
    {
        $exists = TyreAssignment::query()
            ->where('asset_type', $assetType)
            ->where('asset_id', $assetId)
            ->where('position_code', $positionCode)
            ->where('status', TyreAssignmentStatus::Active)
            ->exists();

        if ($exists) {
            throw new TyreBusinessException("Position {$positionCode} already has an active tyre.");
        }
    }

    public function assertTyreHasNoActiveAssignment(int $tyreId): void
    {
        $exists = TyreAssignment::query()
            ->where('tyre_id', $tyreId)
            ->where('status', TyreAssignmentStatus::Active)
            ->exists();

        if ($exists) {
            throw new TyreBusinessException('Tyre already has an active assignment.');
        }
    }

    public function closeActiveAssignment(
        Tyre $tyre,
        ?int $removedOdometer = null,
        ?int $removedBy = null,
        ?int $movementId = null
    ): ?TyreAssignment {
        $assignment = TyreAssignment::query()
            ->where('tyre_id', $tyre->id)
            ->where('status', TyreAssignmentStatus::Active)
            ->lockForUpdate()
            ->first();

        if (! $assignment) {
            return null;
        }

        $kmUsed = null;
        if ($removedOdometer !== null && $assignment->installed_odometer !== null) {
            $kmUsed = max(0, $removedOdometer - $assignment->installed_odometer);
        }

        $assignment->update([
            'removed_date' => now()->toDateString(),
            'removed_odometer' => $removedOdometer,
            'km_used' => $kmUsed,
            'status' => TyreAssignmentStatus::Removed,
            'removed_by' => $removedBy,
            'movement_id' => $movementId ?? $assignment->movement_id,
        ]);

        return $assignment->fresh();
    }

    public function createActiveAssignment(
        Tyre $tyre,
        AssignmentAssetType $assetType,
        Vehicle $vehicle,
        string $positionCode,
        ?int $installedOdometer = null,
        ?int $installedBy = null,
        ?int $movementId = null
    ): TyreAssignment {
        $this->assertPositionEmpty($assetType, $vehicle->id, $positionCode);
        $this->assertTyreHasNoActiveAssignment($tyre->id);

        return TyreAssignment::query()->create([
            'tyre_id' => $tyre->id,
            'asset_type' => $assetType,
            'asset_id' => $vehicle->id,
            'position_code' => $positionCode,
            'installed_date' => now()->toDateString(),
            'installed_odometer' => $installedOdometer ?? $vehicle->odometer,
            'status' => TyreAssignmentStatus::Active,
            'installed_by' => $installedBy,
            'movement_id' => $movementId,
        ]);
    }

    public function updateTyreLocation(
        Tyre $tyre,
        TyreLocationType $locationType,
        ?int $locationId,
        ?string $positionCode,
        TyreStatus $status
    ): void {
        $tyre->update([
            'current_location_type' => $locationType,
            'current_location_id' => $locationId,
            'current_position_code' => $positionCode,
            'status' => $status,
        ]);
    }
}
