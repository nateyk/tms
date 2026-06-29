<?php

namespace App\Http\Controllers\Fleet;

use App\Enums\AssetType;
use App\Enums\PredefinedTyreLayout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleTypeRequest;
use App\Http\Requests\Fleet\UpdateVehicleTypeRequest;
use App\Models\VehicleType;
use App\Services\VehicleTyreLayoutBuilder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VehicleTypeController extends Controller
{
    public function __construct(
        private readonly VehicleTyreLayoutBuilder $layoutBuilder,
    ) {}

    public function index(): Response
    {
        $vehicleTypes = VehicleType::query()
            ->orderBy('name')
            ->paginate(15)
            ->through(fn (VehicleType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'asset_type' => $type->asset_type->value,
                'asset_type_label' => $type->asset_type->label(),
                'axle_count' => $type->axle_count,
                'tyre_count' => $type->tyre_count,
                'spare_count' => $this->spareCount($type->layout_json),
                'status' => $type->status,
            ]);

        return Inertia::render('fleet/vehicle-types/index', [
            'vehicleTypes' => $vehicleTypes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('fleet/vehicle-types/create', $this->formOptions());
    }

    public function store(StoreVehicleTypeRequest $request): RedirectResponse
    {
        $data = $this->preparePayload($request->validated());

        VehicleType::query()->create($data);

        return redirect()
            ->route('fleet.vehicle-types.index')
            ->with('success', 'Vehicle type created successfully.');
    }

    public function edit(VehicleType $vehicleType): Response
    {
        $preset = PredefinedTyreLayout::tryFromTyreCount($vehicleType->tyre_count);

        return Inertia::render('fleet/vehicle-types/edit', [
            ...$this->formOptions(),
            'vehicleType' => [
                'id' => $vehicleType->id,
                'name' => $vehicleType->name,
                'asset_type' => $vehicleType->asset_type->value,
                'status' => $vehicleType->status,
                'layout_preset' => $preset?->value ?? PredefinedTyreLayout::PowerUnit10->value,
                'tyre_count' => $vehicleType->tyre_count,
                'axle_count' => $vehicleType->axle_count,
            ],
        ]);
    }

    public function update(UpdateVehicleTypeRequest $request, VehicleType $vehicleType): RedirectResponse
    {
        $vehicleType->update($this->preparePayload($request->validated()));

        return redirect()
            ->route('fleet.vehicle-types.index')
            ->with('success', 'Vehicle type updated successfully.');
    }

    public function destroy(VehicleType $vehicleType): RedirectResponse
    {
        if ($vehicleType->vehicles()->exists()) {
            return back()->with('error', 'Cannot delete a vehicle type that has vehicles assigned.');
        }

        $vehicleType->delete();

        return redirect()
            ->route('fleet.vehicle-types.index')
            ->with('success', 'Vehicle type deleted successfully.');
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'assetTypes' => collect(AssetType::cases())->map(fn (AssetType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values(),
            'layoutPresets' => collect(PredefinedTyreLayout::cases())->map(fn (PredefinedTyreLayout $preset) => [
                'value' => $preset->value,
                'label' => $preset->label(),
                'description' => $preset->description(),
                'tyre_count' => $preset->tyreCount(),
                'axle_count' => $preset->axleCount(),
                'spare_count' => $preset->spareCount(),
                'asset_type' => $preset->suggestedAssetType()->value,
            ])->values(),
            'defaultPreset' => PredefinedTyreLayout::PowerUnit10->value,
        ];
    }

    /** @param  array<string, mixed>  $validated */
    private function preparePayload(array $validated): array
    {
        $preset = PredefinedTyreLayout::from($validated['layout_preset']);

        $layout = $this->layoutBuilder->buildLayout(
            $preset->tyreCount(),
            $preset->axleCount(),
            $preset->positionPrefix(),
        );

        return [
            'name' => $validated['name'],
            'asset_type' => $validated['asset_type'],
            'status' => $validated['status'],
            'tyre_count' => $preset->tyreCount(),
            'axle_count' => $preset->axleCount(),
            'layout_json' => $layout,
        ];
    }

    /** @param  array<string, mixed>|null  $layoutJson */
    private function spareCount(?array $layoutJson): int
    {
        $positions = $layoutJson['positions'] ?? [];

        return collect($positions)
            ->filter(function (array $position) {
                $code = $position['display_code'] ?? $position['code'] ?? '';

                return in_array($code, ['W', 'X'], true);
            })
            ->count();
    }
}
