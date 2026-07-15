<?php

namespace App\Services;

use App\Enums\AssetType;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBrand;
use App\Models\Vehicle;
use App\Models\VehicleType;

class VehicleMapDataService
{
    public function __construct(
        protected TyreMapWorkflowService $workflow,
        protected VehicleTyreLayoutBuilder $layoutBuilder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildForVehicle(Vehicle $vehicle, ?string $selectedPosition = null): array
    {
        $vehicle->loadMissing('vehicleType');

        $vehicleType = $vehicle->vehicleType;
        $assetType = $vehicle->asset_type instanceof AssetType
            ? $vehicle->asset_type->value
            : (string) $vehicle->asset_type;

        $prefix = match ($assetType) {
            AssetType::Trailer->value => 'T',
            AssetType::RigidTruck->value => 'R',
            default => 'P',
        };

        $tyreCount = $vehicleType instanceof VehicleType ? (int) $vehicleType->tyre_count : 0;
        $axleCount = $vehicleType instanceof VehicleType ? (int) $vehicleType->axle_count : 1;
        $layoutJson = $vehicleType instanceof VehicleType && is_array($vehicleType->layout_json)
            ? $vehicleType->layout_json
            : null;

        $positions = $vehicleType instanceof VehicleType
            ? $this->layoutBuilder->resolvePositions($layoutJson, $tyreCount, $axleCount, $prefix)
            : [];

        $assignmentsByPosition = TyreAssignment::query()
            ->where('asset_id', $vehicle->id)
            ->where('status', TyreAssignmentStatus::Active)
            ->with(['tyre.brand', 'tyre.size'])
            ->get()
            ->keyBy('position_code');

        $spareOwner = $vehicle;
        if ($assetType === AssetType::Trailer->value) {
            $attachedPower = $vehicle->attachedPower();
            if ($attachedPower instanceof Vehicle) {
                $spareOwner = $attachedPower;
            }
        }

        $spareCodes = $assetType === AssetType::Trailer->value ? ['X'] : ['W', 'X'];
        $spareLabel = $assetType === AssetType::Trailer->value ? 'Combination spare' : 'Power spare';

        $assignedSpareTyres = Tyre::query()
            ->where('current_location_type', TyreLocationType::PowerVehicle->value)
            ->where('current_location_id', $spareOwner->id)
            ->whereIn('current_position_code', array_map(fn (string $code): string => 'SPARE-'.$code, $spareCodes))
            ->with('brand')
            ->orderBy('current_position_code')
            ->get()
            ->keyBy(fn (Tyre $tyre): string => str_replace('SPARE-', '', (string) $tyre->current_position_code));

        $spareTyres = collect($spareCodes)
            ->map(function (string $displayCode) use ($assignedSpareTyres, $spareLabel): array {
                $tyre = $assignedSpareTyres->get($displayCode);
                $brand = $tyre instanceof Tyre ? $tyre->brand : null;

                return [
                    'position' => 'SPARE-'.$displayCode,
                    'display_code' => $displayCode,
                    'tyre_code' => $tyre instanceof Tyre ? $tyre->tyre_code : null,
                    'tyre_id' => $tyre instanceof Tyre ? $tyre->id : null,
                    'serial_number' => $tyre instanceof Tyre ? $tyre->serial_number : null,
                    'brand' => $brand instanceof TyreBrand ? $brand->name : null,
                    'status' => $this->resolveTyreStatus($tyre)?->label() ?? 'Open spare pocket',
                    'owner_label' => $spareLabel,
                ];
            });

        $mapData = collect($positions)->map(function (array $position) use ($assignmentsByPosition, $vehicle) {
            $assignment = collect($this->workflow->positionAliases($position))
                ->map(fn (string $code) => $assignmentsByPosition->get($code))
                ->first();

            $tyre = $assignment?->tyre;
            $brand = $tyre instanceof Tyre ? $tyre->brand : null;
            $tyreStatus = $this->resolveTyreStatus($tyre);

            return [
                'code' => $position['code'],
                'display_code' => $position['display_code'] ?? $position['code'],
                'legacy_code' => $position['legacy_code'] ?? null,
                'label' => $position['label'] ?? $position['code'],
                'axle' => $position['axle'] ?? null,
                'side' => $position['side'] ?? null,
                'dual' => $position['dual'] ?? null,
                'x' => (int) ($position['x'] ?? 0),
                'y' => (int) ($position['y'] ?? 0),
                'tyre_code' => $tyre instanceof Tyre ? $tyre->tyre_code : null,
                'tyre_id' => $tyre instanceof Tyre ? $tyre->id : null,
                'serial_number' => $tyre instanceof Tyre ? $tyre->serial_number : null,
                'brand' => $brand instanceof TyreBrand ? $brand->name : null,
                'tread_depth' => $tyre instanceof Tyre ? $tyre->current_tread_depth : null,
                'status' => $tyreStatus?->label() ?? 'Empty',
                'status_value' => $tyreStatus !== null ? $tyreStatus->value : 'empty',
                'color' => $tyreStatus?->mapColor() ?? 'gray',
                'install_url' => $tyre ? null : $this->workflow->installMovementUrl($vehicle, $position['code']),
            ];
        })->values();

        $emptySlots = $this->workflow->emptyPositions($vehicle)->map(function (array $slot) use ($vehicle) {
            return array_merge($slot, [
                'install_url' => $this->workflow->installMovementUrl($vehicle, $slot['code']),
            ]);
        })->values();

        $spareMapSlots = $spareTyres->map(fn (array $spare): array => [
            'code' => $spare['position'],
            'display_code' => $spare['display_code'],
            'label' => $spare['owner_label'].' '.$spare['display_code'],
            'axle' => null,
            'side' => 'center',
            'dual' => 'spare',
            'x' => 0,
            'y' => 0,
            'tyre_code' => $spare['tyre_code'],
            'color' => $spare['tyre_code'] ? 'blue' : 'gray',
        ]);

        $konvaSlots = $mapData->map(fn (array $slot) => [
            'code' => $slot['code'],
            'display_code' => $slot['display_code'],
            'label' => $slot['label'],
            'axle' => $slot['axle'],
            'side' => $slot['side'],
            'dual' => $slot['dual'],
            'x' => $slot['x'],
            'y' => $slot['y'],
            'tyre_code' => $slot['tyre_code'],
            'color' => $slot['color'],
        ])->concat($spareMapSlots)->values()->all();

        $selectedSlot = $selectedPosition
            ? $mapData->firstWhere('code', $selectedPosition)
            : null;

        if (! $selectedSlot && $selectedPosition) {
            $selectedSlot = $spareTyres->firstWhere('position', $selectedPosition);
        }

        return [
            'mapData' => $mapData->all(),
            'emptySlots' => $emptySlots->all(),
            'spareTyres' => $spareTyres->values()->all(),
            'spareCapacity' => $spareTyres->count(),
            'konvaConfig' => [
                'slots' => $konvaSlots,
                'selectedPosition' => $selectedPosition,
                'assetType' => $assetType,
            ],
            'counts' => [
                'mounted' => $mapData->whereNotNull('tyre_code')->count(),
                'total' => $mapData->count(),
                'empty' => $mapData->whereNull('tyre_code')->count(),
                'spares_filled' => $spareTyres->whereNotNull('tyre_code')->count(),
            ],
            'selectedSlot' => $selectedSlot,
            'legend' => [
                'green' => 'Active',
                'blue' => 'Available',
                'orange' => 'Maintenance',
                'red' => 'Damaged',
                'yellow' => 'Pending',
                'black' => 'Disposed',
                'gray' => 'Empty',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildShowPayload(Vehicle $vehicle): array
    {
        $vehicle->load([
            'vehicleType',
            'currentLocation',
            'activeCombinationAsPower.trailer.vehicleType',
            'activeCombinationAsTrailer.powerVehicle',
        ]);

        $map = $this->buildForVehicle($vehicle);

        $payload = [
            'vehicle' => $this->serializeVehicle($vehicle),
            'tyreMap' => $map,
        ];

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeVehicle(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'vehicle_code' => $vehicle->vehicle_code,
            'plate_number' => $vehicle->plate_number,
            'chassis_number' => $vehicle->chassis_number,
            'engine_number' => $vehicle->engine_number,
            'manufacture_year' => $vehicle->manufacture_year,
            'asset_type' => $vehicle->asset_type->value,
            'asset_type_label' => $vehicle->asset_type->label(),
            'vehicle_type_id' => $vehicle->vehicle_type_id,
            'vehicle_type_name' => $vehicle->vehicleType?->name,
            'status' => $vehicle->status->value,
            'status_label' => $vehicle->status->label(),
            'current_location_name' => $vehicle->currentLocation?->name,
            'odometer' => $vehicle->odometer,
            'notes' => $vehicle->notes,
            'display_code' => $vehicle->displayCodeWithPlate(),
            'attached_trailer_code' => $vehicle->attachedTrailer()?->vehicle_code,
            'attached_power_code' => $vehicle->attachedPower()?->vehicle_code,
        ];
    }

    private function resolveTyreStatus(mixed $tyre): ?TyreStatus
    {
        if (! $tyre instanceof Tyre) {
            return null;
        }

        $status = $tyre->status;

        return $status instanceof TyreStatus ? $status : TyreStatus::tryFrom((string) $status);
    }
}
