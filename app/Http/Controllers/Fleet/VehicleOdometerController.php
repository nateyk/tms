<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\UpdateVehicleOdometerRequest;
use App\Models\Vehicle;
use App\Models\VehicleOdometerReading;
use App\Services\VehicleOdometerService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VehicleOdometerController extends Controller
{
    public function __construct(
        private readonly VehicleOdometerService $odometerService,
    ) {}

    public function edit(Vehicle $vehicle): Response
    {
        $this->authorize('vehicle.odometer.update');

        $vehicle->load(['currentLocation']);

        $latestReading = $this->odometerService->getLatestReading($vehicle);
        $readingHistory = $this->odometerService->getReadingHistory($vehicle, 20);

        return Inertia::render('fleet/vehicles/odometer', [
            'vehicle' => $this->serializeVehicle($vehicle),
            'latest_reading' => $latestReading ? $this->serializeReading($latestReading) : null,
            'reading_history' => $readingHistory->map(fn ($reading) => $this->serializeReading($reading)),
        ]);
    }

    public function update(UpdateVehicleOdometerRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->odometerService->updateOdometer(
            $vehicle,
            $request->validated('odometer'),
            'manual',
            null,
            (int) auth()->id(),
        );

        return back()->with('success', 'Odometer updated successfully.');
    }

    public function history(Vehicle $vehicle): Response
    {
        $this->authorize('vehicle.odometer.update');

        $readings = $this->odometerService->getReadingHistory($vehicle, 50);

        return Inertia::render('fleet/vehicles/odometer-history', [
            'vehicle' => $this->serializeVehicle($vehicle),
            'readings' => $readings->map(fn ($reading) => $this->serializeReading($reading)),
        ]);
    }

    private function serializeVehicle(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'vehicle_code' => $vehicle->vehicle_code,
            'plate_number' => $vehicle->plate_number,
            'display_code_with_plate' => $vehicle->displayCodeWithPlate(),
            'odometer' => $vehicle->odometer,
            'odometer_last_updated_at' => $vehicle->odometer_last_updated_at?->toDateTimeString(),
        ];
    }

    private function serializeReading(VehicleOdometerReading $reading): array
    {
        return [
            'id' => $reading->id,
            'odometer' => $reading->odometer,
            'reading_date' => $reading->reading_date?->format('Y-m-d'),
            'source' => $reading->source->value,
            'source_label' => $reading->source->label(),
            'source_id' => $reading->source_id,
            'recorded_by' => $reading->recordedBy?->name,
            'notes' => $reading->notes,
            'created_at' => $reading->created_at?->toDateTimeString(),
        ];
    }
}
