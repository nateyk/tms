<?php

namespace App\Services;

use App\Enums\AssetType;
use App\Enums\AssignmentAssetType;
use App\Enums\MovementType;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
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
        $assetType = $this->assetTypeValue($vehicle);

        return match ($assetType) {
            AssetType::Trailer->value => TyreLocationType::Trailer,
            AssetType::PowerVehicle->value, AssetType::RigidTruck->value => TyreLocationType::PowerVehicle,
            default => TyreLocationType::PowerVehicle,
        };
    }

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
            ->filter(fn (array $position) => collect($this->positionAliases($position))->every(
                fn (string $code): bool => ! $occupied->has($code)
            ))
            ->values()
            ->map(fn (array $position) => [
                'code' => (string) $position['code'],
                'display_code' => (string) ($position['display_code'] ?? $position['code']),
                'legacy_code' => isset($position['legacy_code']) ? (string) $position['legacy_code'] : null,
                'label' => (string) ($position['label'] ?? $position['code']),
                'axle' => isset($position['axle']) ? (int) $position['axle'] : null,
                'side' => isset($position['side']) ? (string) $position['side'] : null,
                'dual' => isset($position['dual']) ? (string) $position['dual'] : null,
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

        $assetType = $this->assetTypeValue($vehicle);
        $prefix = match ($assetType) {
            AssetType::Trailer->value => 'T',
            AssetType::RigidTruck->value => 'R',
            default => 'P',
        };
        $layoutJson = is_array($vehicleType->layout_json) ? $vehicleType->layout_json : null;

        return $this->layoutBuilder->resolvePositions(
            $layoutJson,
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
            $code = (string) $position['code'];
            $displayCode = (string) ($position['display_code'] ?? $code);
            $label = (string) ($position['label'] ?? $code);
            $options[$code] = "{$displayCode} - {$label}";
        }

        return $options;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function positionStatusForVehicle(Vehicle $vehicle): array
    {
        $vehicle->loadMissing('vehicleType');

        $assignments = TyreAssignment::query()
            ->with('tyre:id,tyre_code')
            ->where('asset_id', $vehicle->id)
            ->where('asset_type', $this->assignmentAssetTypeForVehicle($vehicle)?->value)
            ->where('status', TyreAssignmentStatus::Active)
            ->get();

        return collect($this->resolveLayoutPositions($vehicle))
            ->map(function (array $position) use ($assignments): array {
                $aliases = $this->positionAliases($position);
                $assignment = $assignments->first(fn (TyreAssignment $assignment): bool => in_array($assignment->position_code, $aliases, true));
                $code = (string) $position['code'];
                $displayCode = (string) ($position['display_code'] ?? $code);
                $label = (string) ($position['label'] ?? $code);
                $isSpare = $this->isSparePosition($position);

                return [
                    'code' => $code,
                    'display_code' => $displayCode,
                    'label' => $label,
                    'type' => $isSpare ? 'spare' : 'running',
                    'is_empty' => $assignment === null,
                    'is_occupied' => $assignment !== null,
                    'mounted_tyre_id' => $assignment?->tyre_id,
                    'mounted_tyre_code' => $assignment?->tyre?->tyre_code,
                    'disabled_reason' => $assignment ? 'This position already has a tyre. Create a swap movement or choose an empty position.' : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function positionAliasesForVehicle(Vehicle $vehicle, string $positionCode): array
    {
        foreach ($this->resolveLayoutPositions($vehicle) as $position) {
            $aliases = $this->positionAliases($position);

            if (in_array($positionCode, $aliases, true)) {
                return $aliases;
            }
        }

        return [$positionCode];
    }

    public function isSparePositionForVehicle(Vehicle $vehicle, string $positionCode): bool
    {
        foreach ($this->resolveLayoutPositions($vehicle) as $position) {
            if (in_array($positionCode, $this->positionAliases($position), true)) {
                return $this->isSparePosition($position);
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $position
     * @return list<string>
     */
    public function positionAliases(array $position): array
    {
        return collect([
            $position['code'] ?? null,
            $position['display_code'] ?? null,
            $position['legacy_code'] ?? null,
        ])
            ->filter(fn ($code): bool => filled($code))
            ->map(fn ($code): string => (string) $code)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $position
     */
    public function isSparePosition(array $position): bool
    {
        $displayCode = strtoupper((string) ($position['display_code'] ?? $position['code'] ?? ''));
        $label = strtolower((string) ($position['label'] ?? ''));

        return in_array($displayCode, ['W', 'X'], true)
            || ($position['side'] ?? null) === 'center'
            || str_contains($label, 'spare');
    }

    public function assignmentAssetTypeForVehicle(Vehicle $vehicle): ?AssignmentAssetType
    {
        return match ($this->assetTypeValue($vehicle)) {
            AssetType::Trailer->value => AssignmentAssetType::Trailer,
            AssetType::PowerVehicle->value, AssetType::RigidTruck->value => AssignmentAssetType::PowerVehicle,
            default => null,
        };
    }

    public function installMovementUrl(Vehicle $vehicle, string $positionCode, ?int $tyreId = null): string
    {
        $query = array_filter([
            'vehicle_id' => $vehicle->id,
            'position' => $positionCode,
            'movement_type' => MovementType::StoreToVehicle->value,
            'tyre_id' => $tyreId,
        ], fn ($value) => $value !== null && $value !== '');

        return route('tyres.movements.create').'?'.http_build_query($query);
    }

    public function viewTyreUrl(int $tyreId): string
    {
        return route('tyres.show', $tyreId);
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
        $vehicleId = (int) request()->query('destination_vehicle_id', request()->query('vehicle_id', 0));
        $position = (string) request()->query('destination_position', request()->query('position', ''));
        $tyreId = (int) request()->query('tyre_id', 0);
        $movementType = (string) request()->query('movement_type', MovementType::StoreToVehicle->value);
        $sourceVehicleId = (int) request()->query('source_vehicle_id', 0);
        $sourcePosition = (string) request()->query('source_position', '');

        if ($vehicleId <= 0 || $position === '') {
            if ($tyreId <= 0) {
                return [];
            }

            $tyre = Tyre::query()->find($tyreId);
            if (! $tyre) {
                return [];
            }

            return array_filter([
                'tyre_id' => $tyreId,
                'movement_date' => now()->toDateString(),
                'reason' => $sourceVehicleId > 0 && $sourcePosition !== ''
                    ? "Move tyre from {$sourcePosition}"
                    : "Create movement for {$tyre->tyre_code}",
            ], fn ($value) => $value !== null && $value !== '');
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
                $data['from_location_type'] = $tyre->current_location_type instanceof TyreLocationType
                    ? $tyre->current_location_type->value
                    : (string) $tyre->current_location_type;
                $data['from_location_id'] = $tyre->current_location_id;
                $data['from_position_code'] = $tyre->current_position_code;
            }
        }

        return $data;
    }

    private function assetTypeValue(Vehicle $vehicle): string
    {
        return $vehicle->asset_type instanceof AssetType
            ? $vehicle->asset_type->value
            : (string) $vehicle->asset_type;
    }
}
