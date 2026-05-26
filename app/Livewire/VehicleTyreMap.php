<?php

namespace App\Livewire;

use App\Enums\AssetType;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreStatus;
use App\Filament\Resources\TyreMovements\TyreMovementResource;
use App\Models\Tyre;
use App\Models\TyreAssignment;
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
        $prefix = $vehicle->asset_type === AssetType::Trailer ? 'T' : ($vehicle->asset_type === AssetType::RigidTruck ? 'R' : 'P');
        $tyreCount = $vehicleType instanceof VehicleType ? (int) $vehicleType->tyre_count : 0;
        $axleCount = $vehicleType instanceof VehicleType ? (int) $vehicleType->axle_count : 1;

        $positions = $vehicleType instanceof VehicleType
            ? $layoutBuilder->resolvePositions(
                $vehicleType->layout_json,
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

        $mapData = collect($positions)->map(function (array $position) use ($assignments, $workflow, $vehicle) {
            $assignment = $assignments->get($position['code']);
            $tyre = $assignment?->tyre;
            $tyreStatus = $this->resolveTyreStatus($tyre);

            return [
                'code' => $position['code'],
                'label' => $position['label'] ?? $position['code'],
                'axle' => $position['axle'] ?? null,
                'side' => $position['side'] ?? null,
                'dual' => $position['dual'] ?? null,
                'x' => (int) ($position['x'] ?? 0),
                'y' => (int) ($position['y'] ?? 0),
                'tyre_code' => $tyre instanceof Tyre ? $tyre->tyre_code : null,
                'tyre_id' => $tyre instanceof Tyre ? $tyre->id : null,
                'serial_number' => $tyre instanceof Tyre ? $tyre->serial_number : null,
                'brand' => $tyre?->brand?->name,
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

        $konvaConfig = [
            'slots' => $mapData->map(fn (array $slot) => [
                'code' => $slot['code'],
                'label' => $slot['label'],
                'axle' => $slot['axle'],
                'side' => $slot['side'],
                'dual' => $slot['dual'],
                'x' => $slot['x'],
                'y' => $slot['y'],
                'tyre_code' => $slot['tyre_code'],
                'color' => $slot['color'],
            ])->values()->all(),
            'selectedPosition' => $this->selectedPosition,
        ];

        return view('livewire.vehicle-tyre-map', [
            'vehicle' => $vehicle,
            'mapData' => $mapData,
            'emptySlots' => $emptySlots,
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
