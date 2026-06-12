<?php

namespace App\Livewire;

use App\Enums\AssetType;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Filament\Resources\TyreMovements\TyreMovementResource;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBrand;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\TyreMapWorkflowService;
use App\Services\VehicleTyreLayoutBuilder;
use Livewire\Component;

class VehicleTyreMap extends Component
{
    public int $vehicleId;

    public ?string $selectedPosition = null;

    public ?int $selectedTyreId = null;

    public function mount(int $vehicleId, ?string $position = null): void
    {
        $this->vehicleId = $vehicleId;

        if ($position) {
            $this->selectPosition($position);
        }
    }

    public function selectPosition(string $positionCode): void
    {
        $this->selectedPosition = $positionCode;
        $assignment = TyreAssignment::query()
            ->where('asset_id', $this->vehicleId)
            ->where('position_code', $positionCode)
            ->where('status', TyreAssignmentStatus::Active)
            ->with('tyre.brand')
            ->first();

        $this->selectedTyreId = $assignment?->tyre_id;
    }

    public function render()
    {
        $vehicle = Vehicle::query()->with('vehicleType')->findOrFail($this->vehicleId);
        $workflow = app(TyreMapWorkflowService::class);
        $layoutBuilder = app(VehicleTyreLayoutBuilder::class);

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
            ? $layoutBuilder->resolvePositions(
                $layoutJson,
                $tyreCount,
                $axleCount,
                $prefix,
            )
            : [];

        $assignments = TyreAssignment::query()
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

        $spareCodes = ['W', 'X'];
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
                if (! $brand instanceof TyreBrand) {
                    $brand = null;
                }

                return [
                    'position' => 'SPARE-'.$displayCode,
                    'display_code' => $displayCode,
                    'tyre_code' => $tyre instanceof Tyre ? $tyre->tyre_code : null,
                    'serial_number' => $tyre instanceof Tyre ? $tyre->serial_number : null,
                    'brand' => $brand?->name,
                    'status' => $this->resolveTyreStatus($tyre)?->label() ?? 'Open spare pocket',
                    'owner_label' => $spareLabel,
                ];
            });

        /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $positionCollection */
        $positionCollection = collect($positions);

        $mapData = $positionCollection->map(function (array $position) use ($assignments, $workflow, $vehicle) {
            $assignment = $assignments->get($position['code']);
            $tyre = $assignment?->tyre;
            if (! $tyre instanceof Tyre) {
                $tyre = null;
            }

            $brand = $tyre?->brand;
            if (! $brand instanceof TyreBrand) {
                $brand = null;
            }

            $tyreStatus = $this->resolveTyreStatus($tyre);

            return [
                'code' => $position['code'],
                'display_code' => $position['display_code'] ?? $position['code'],
                'label' => $position['label'] ?? $position['code'],
                'axle' => $position['axle'] ?? null,
                'side' => $position['side'] ?? null,
                'dual' => $position['dual'] ?? null,
                'x' => (int) ($position['x'] ?? 0),
                'y' => (int) ($position['y'] ?? 0),
                'tyre_code' => $tyre instanceof Tyre ? $tyre->tyre_code : null,
                'tyre_id' => $tyre instanceof Tyre ? $tyre->id : null,
                'serial_number' => $tyre instanceof Tyre ? $tyre->serial_number : null,
                'brand' => $brand?->name,
                'tread_depth' => $tyre instanceof Tyre ? $tyre->current_tread_depth : null,
                'status' => $tyreStatus?->label() ?? 'Empty',
                'status_value' => $tyreStatus !== null ? $tyreStatus->value : 'empty',
                'color' => $tyreStatus?->mapColor() ?? 'gray',
                'install_url' => $tyre ? null : $workflow->installMovementUrl($vehicle, $position['code']),
            ];
        });

        $emptySlots = $workflow->emptyPositions($vehicle)->map(function (array $slot) use ($vehicle, $workflow) {
            return array_merge($slot, [
                'install_url' => $workflow->installMovementUrl($vehicle, $slot['code']),
            ]);
        });

        $spareMapSlots = $assetType === AssetType::Trailer->value
            ? collect()
            : $spareTyres->map(fn (array $spare): array => [
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

        $konvaConfig = [
            'slots' => $mapData->map(fn (array $slot) => [
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
            ])->concat($spareMapSlots)->values()->all(),
            'selectedPosition' => $this->selectedPosition,
            'assetType' => $assetType,
        ];

        return view('livewire.vehicle-tyre-map', [
            'vehicle' => $vehicle,
            'mapData' => $mapData,
            'emptySlots' => $emptySlots,
            'spareTyres' => $spareTyres,
            'spareCapacity' => $spareTyres->count(),
            'konvaConfig' => $konvaConfig,
            'movementsIndexUrl' => TyreMovementResource::getUrl('index'),
            'legend' => [
                'green' => 'Active',
                'blue' => 'Available',
                'orange' => 'Maintenance',
                'red' => 'Damaged',
                'yellow' => 'Pending',
                'black' => 'Disposed',
                'gray' => 'Empty',
            ],
        ]);
    }

    private function resolveTyreStatus(mixed $tyre): ?TyreStatus
    {
        if (! $tyre instanceof Tyre) {
            return null;
        }

        $status = $tyre->status;

        if ($status instanceof TyreStatus) {
            return $status;
        }

        return TyreStatus::tryFrom((string) $status);
    }
}
