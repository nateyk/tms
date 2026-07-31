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

        $combinedVehicle = $this->combinedVehicle($vehicle);
        if ($combinedVehicle) {
            $this->validateOdometerNotLower($combinedVehicle, $odometer);
        }

        $reading = $this->writeOdometerReading($vehicle, $odometer, $source, $sourceId, $userId, $notes);

        // A power unit and its active trailer share the trip KM. Keep both
        // vehicle records current so trailer-mounted tyres calculate usage.
        if ($combinedVehicle) {
            $this->writeOdometerReading($combinedVehicle, $odometer, $source, $sourceId, $userId, $notes);
        }

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
                $this->syncCombinedVehicleOdometer($vehicle, $odometer, OdometerReadingSource::Movement->value, $movementId, $userId);

                return $existing;
            }

            $this->validateOdometerNotLower($vehicle, $odometer);

            $reading = $this->writeOdometerReading(
                $vehicle,
                $odometer,
                OdometerReadingSource::Movement->value,
                $movementId,
                $userId,
            );
            $this->syncCombinedVehicleOdometer($vehicle, $odometer, OdometerReadingSource::Movement->value, $movementId, $userId);

            return $reading;
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
        $latest = $this->latestStoredOdometer($vehicle);
        $combinedVehicle = $this->combinedVehicle($vehicle);

        if ($combinedVehicle) {
            $combinedLatest = $this->latestStoredOdometer($combinedVehicle);
            $values = array_filter([$latest, $combinedLatest], static fn ($value) => $value !== null);
            $latest = $values === [] ? null : max($values);
        }

        return $latest;
    }

    private function syncCombinedVehicleOdometer(
        Vehicle $vehicle,
        int $odometer,
        string $source,
        ?int $sourceId,
        int $userId,
        ?string $notes = null,
    ): void {
        $combinedVehicle = $this->combinedVehicle($vehicle);

        if (! $combinedVehicle) {
            return;
        }

        $this->validateOdometerNotLower($combinedVehicle, $odometer);
        $this->writeOdometerReading($combinedVehicle, $odometer, $source, $sourceId, $userId, $notes);
    }

    private function writeOdometerReading(
        Vehicle $vehicle,
        int $odometer,
        string $source,
        ?int $sourceId,
        int $userId,
        ?string $notes = null,
    ): VehicleOdometerReading {
        $reading = $sourceId !== null
            ? VehicleOdometerReading::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('source', $source)
                ->where('source_id', $sourceId)
                ->first()
            : null;

        $attributes = [
            'odometer' => $odometer,
            'reading_date' => now()->toDateString(),
            'source' => $source,
            'source_id' => $sourceId,
            'recorded_by' => $userId,
            'notes' => $notes,
        ];

        if ($reading) {
            $reading->update($attributes);
        } else {
            $reading = VehicleOdometerReading::query()->create(array_merge(
                ['vehicle_id' => $vehicle->id],
                $attributes,
            ));
        }

        $vehicle->forceFill([
            'odometer' => $odometer,
            'odometer_last_updated_at' => now(),
            'odometer_last_updated_by' => $userId,
        ])->save();

        return $reading->fresh();
    }

    private function combinedVehicle(Vehicle $vehicle): ?Vehicle
    {
        $vehicle->loadMissing([
            'activeCombinationAsPower.trailer',
            'activeCombinationAsTrailer.powerVehicle',
        ]);

        if ($vehicle->isPowerVehicle()) {
            return $vehicle->activeCombinationAsPower?->trailer;
        }

        if ($vehicle->isTrailer()) {
            return $vehicle->activeCombinationAsTrailer?->powerVehicle;
        }

        return null;
    }

    private function latestStoredOdometer(Vehicle $vehicle): ?int
    {
        $reading = $this->getLatestReading($vehicle);

        return $reading?->odometer ?? $vehicle->odometer;
    }
}
