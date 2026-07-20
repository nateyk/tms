<?php

namespace App\Services;

use App\Enums\OdometerReadingSource;
use App\Exceptions\TyreBusinessException;
use App\Models\Vehicle;
use App\Models\VehicleOdometerReading;

class VehicleOdometerService
{
    public function updateOdometer(
        Vehicle $vehicle,
        int $odometer,
        string $source,
        ?int $sourceId,
        int $userId,
        ?string $notes = null,
    ): VehicleOdometerReading {
        $this->validateOdometerNotLower($vehicle, $odometer);

        $reading = VehicleOdometerReading::query()->create([
            'vehicle_id' => $vehicle->id,
            'odometer' => $odometer,
            'reading_date' => now()->toDateString(),
            'source' => $source,
            'source_id' => $sourceId,
            'recorded_by' => $userId,
            'notes' => $notes,
        ]);

        // Update vehicle's current odometer
        $vehicle->odometer = $odometer;
        $vehicle->odometer_last_updated_at = now();
        $vehicle->odometer_last_updated_by = $userId;
        $vehicle->save();

        return $reading;
    }

    public function validateOdometerNotLower(Vehicle $vehicle, int $odometer): void
    {
        $latestOdometer = $this->getLatestOdometer($vehicle);

        if ($latestOdometer !== null && $odometer < $latestOdometer) {
            throw new TyreBusinessException(
                "Odometer reading ({$odometer}) cannot be lower than the latest recorded odometer ({$latestOdometer})."
            );
        }
    }

    public function getLatestReading(Vehicle $vehicle): ?VehicleOdometerReading
    {
        return VehicleOdometerReading::query()
            ->forVehicle($vehicle->id)
            ->latestReading()
            ->first();
    }

    public function getReadingHistory(Vehicle $vehicle, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return VehicleOdometerReading::query()
            ->forVehicle($vehicle->id)
            ->latestReading()
            ->limit($limit)
            ->get();
    }

    public function recordMovementOdometer(
        Vehicle $vehicle,
        int $odometer,
        int $movementId,
        int $userId
    ): VehicleOdometerReading {
        $existing = VehicleOdometerReading::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('source', OdometerReadingSource::Movement->value)
            ->where('source_id', $movementId)
            ->first();

        if ($existing) {
            if ((int) $existing->odometer === $odometer) {
                return $existing;
            }

            $this->validateOdometerNotLower($vehicle, $odometer);

            $existing->update([
                'odometer' => $odometer,
                'reading_date' => now()->toDateString(),
                'recorded_by' => $userId,
            ]);

            $vehicle->forceFill([
                'odometer' => $odometer,
                'odometer_last_updated_at' => now(),
                'odometer_last_updated_by' => $userId,
            ])->save();

            return $existing->fresh();
        }

        return $this->updateOdometer(
            $vehicle,
            $odometer,
            OdometerReadingSource::Movement->value,
            $movementId,
            $userId
        );
    }

    public function getLatestOdometer(Vehicle $vehicle): ?int
    {
        $reading = $this->getLatestReading($vehicle);

        return $reading?->odometer ?? $vehicle->odometer;
    }
}
