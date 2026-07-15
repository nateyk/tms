<?php

namespace App\Http\Controllers\Fleet;

use App\Enums\AssetType;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleRequest;
use App\Http\Requests\Fleet\UpdateVehicleRequest;
use App\Models\Location;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Services\VehicleMapDataService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleMapDataService $mapDataService,
    ) {}

    public function index(): Response
    {
        $vehicles = Vehicle::query()
            ->with(['vehicleType:id,name', 'currentLocation:id,name'])
            ->orderBy('vehicle_code')
            ->paginate(15)
            ->through(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'vehicle_code' => $vehicle->vehicleCodeDisplay(),
                'plate_number' => $vehicle->plate_number,
                'asset_type' => $vehicle->asset_type->value,
                'asset_type_label' => $vehicle->asset_type->label(),
                'vehicle_type_name' => $vehicle->vehicleType?->name,
                'status' => $vehicle->status->value,
                'status_label' => $vehicle->status->label(),
                'current_location_name' => $vehicle->currentLocation?->name,
                'odometer' => $vehicle->odometer,
            ]);

        return Inertia::render('fleet/vehicles/index', [
            'vehicles' => $vehicles,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('fleet/vehicles/create', $this->formOptions());
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['vehicle_code']);

        $vehicle = Vehicle::query()->create($data);

        return redirect()
            ->route('fleet.vehicles.show', $vehicle)
            ->with('success', 'Vehicle created successfully.');
    }

    public function show(Vehicle $vehicle): Response
    {
        return Inertia::render('fleet/vehicles/show', $this->mapDataService->buildShowPayload($vehicle));
    }

    public function edit(Vehicle $vehicle): Response
    {
        return Inertia::render('fleet/vehicles/edit', [
            ...$this->formOptions(),
            'vehicle' => [
                'id' => $vehicle->id,
                'vehicle_code' => $vehicle->vehicle_code,
                'plate_number' => $vehicle->plate_number ?? '',
                'chassis_number' => $vehicle->chassis_number ?? '',
                'engine_number' => $vehicle->engine_number ?? '',
                'asset_type' => $vehicle->asset_type->value,
                'vehicle_type_id' => $vehicle->vehicle_type_id,
                'status' => $vehicle->status->value,
                'current_location_id' => $vehicle->current_location_id,
                'manufacture_year' => $vehicle->manufacture_year,
                'odometer' => $vehicle->odometer,
                'notes' => $vehicle->notes ?? '',
            ],
        ]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $request->validated();
        unset($data['vehicle_code']);

        $vehicle->update($data);

        return redirect()
            ->route('fleet.vehicles.show', $vehicle)
            ->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->activeTyreAssignments()->exists()) {
            return back()->with('error', 'Cannot delete a vehicle with active tyre assignments.');
        }

        $vehicle->delete();

        return redirect()
            ->route('fleet.vehicles.index')
            ->with('success', 'Vehicle deleted successfully.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'assetTypes' => collect(AssetType::cases())->map(fn (AssetType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values(),
            'vehicleStatuses' => collect(VehicleStatus::cases())->map(fn (VehicleStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
            'vehicleTypes' => VehicleType::query()
                ->orderBy('name')
                ->get(['id', 'name', 'asset_type'])
                ->map(fn (VehicleType $type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'asset_type' => $type->asset_type->value,
                ]),
            'locations' => Location::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn (Location $location) => [
                    'id' => $location->id,
                    'label' => $location->code ? "{$location->code} — {$location->name}" : $location->name,
                ]),
        ];
    }
}
