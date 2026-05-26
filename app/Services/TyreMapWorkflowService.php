<?php

namespace App\Services;

use App\Enums\AssetType;
use App\Enums\MovementType;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Filament\Resources\TyreMovements\TyreMovementResource;
use App\Filament\Resources\Tyres\TyreResource;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Support\Collection;

class TyreMapWorkflowService
{
    public function __construct(
        protected VehicleTyreLayoutBuilder $layoutBuilder,
    ) {}

    public function locationTypeForVehicle(Vehicle $vehicle): TyreLocationType
    {
        return match ($vehicle->asset_type) {
            AssetType::Trailer => TyreLocationType::Trailer,
            AssetType::PowerVehicle, AssetType::RigidTruck => TyreLocationType::PowerVehicle,
            default => TyreLocationType::PowerVehicle,
        };
    }

    /**
     * @return Collection<int, array{code: string, label: string, axle: int|null, side: string|null, dual: string|null}>
     */
    public function emptyPositions(Vehicle $vehicle): Collection
    {
        $vehicle->loadMissing('vehicleType');

        $positions = $this->resolveLayoutPositions($vehicle);
        if ($positions === []) {
            return collect();
        }

        $occupied = TyreAssignment::query()
            ->where('asset_id', $vehicle->id)
            ->where('status', TyreAssignmentStatus::Active)
            ->pluck('position_code')
            ->flip();

        return collect($positions)
            ->filter(fn (array $position) => ! $occupied->has($position['code']))
            ->values()
            ->map(fn (array $position) => [
                'code' => $position['code'],
                'label' => $position['label'] ?? $position['code'],
                'axle' => $position['axle'] ?? null,
                'side' => $position['side'] ?? null,
                'dual' => $position['dual'] ?? null,
            ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resolveLayoutPositions(Vehicle $vehicle): array
    {
        $vehicleType = $vehicle->vehicleType;
        if (! $vehicleType instanceof VehicleType) {
            return [];
        }

        $prefix = $vehicle->asset_type === AssetType::Trailer ? 'T' : ($vehicle->asset_type === AssetType::RigidTruck ? 'R' : 'P');

        return $this->layoutBuilder->resolvePositions(
            $vehicleType->layout_json,
            (int) $vehicleType->tyre_count,
            (int) $vehicleType->axle_count,
            $prefix,
        );
    }

    /**
     * @return array<string, string>
     */
    public function positionOptionsForVehicle(int $vehicleId): array
    {
        $vehicle = Vehicle::query()->with('vehicleType')->find($vehicleId);
        if (! $vehicle) {
            return [];
        }

        $options = [];
        foreach ($this->resolveLayoutPositions($vehicle) as $position) {
            $code = $position['code'];
            $label = $position['label'] ?? $code;
            $options[$code] = "{$code} — {$label}";
        }

        return $options;
    }

    public function installMovementUrl(Vehicle $vehicle, string $positionCode, ?int $tyreId = null): string
    {
        $query = array_filter([
            'vehicle_id' => $vehicle->id,
            'position' => $positionCode,
            'movement_type' => MovementType::StoreToVehicle->value,
            'tyre_id' => $tyreId,
        ], fn ($value) => $value !== null && $value !== '');

        return TyreMovementResource::getUrl('create').'?'.http_build_query($query);
    }

    public function viewTyreUrl(int $tyreId): string
    {
        return TyreResource::getUrl('view', ['record' => $tyreId]);
    }

    /**
     * @return Collection<int, Tyre>
     */
    public function storeTyresAvailableForInstall(): Collection
    {
        return Tyre::query()
            ->where('status', TyreStatus::Available)
            ->where('current_location_type', TyreLocationType::Store)
            ->orderBy('tyre_code')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function prefilledMovementFromRequest(): array
    {
        $vehicleId = (int) request()->query('vehicle_id', 0);
        $position = (string) request()->query('position', '');
        $tyreId = (int) request()->query('tyre_id', 0);
        $movementType = (string) request()->query('movement_type', MovementType::StoreToVehicle->value);

        if ($vehicleId <= 0 || $position === '') {
            return [];
        }

        $vehicle = Vehicle::query()->find($vehicleId);
        if (! $vehicle) {
            return [];
        }

        $data = [
            'movement_type' => $movementType,
            'to_location_type' => $this->locationTypeForVehicle($vehicle)->value,
            'to_location_id' => $vehicleId,
            'to_position_code' => $position,
            'movement_date' => now()->toDateString(),
            'reason' => "Install tyre at {$position} on {$vehicle->vehicle_code}",
        ];

        if ($tyreId > 0) {
            $tyre = Tyre::query()->find($tyreId);
            if ($tyre) {
                $data['tyre_id'] = $tyreId;
                $data['from_location_type'] = $tyre->current_location_type?->value;
                $data['from_location_id'] = $tyre->current_location_id;
                $data['from_position_code'] = $tyre->current_position_code;
            }
        }

        return $data;
    }
}
