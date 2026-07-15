<?php

namespace App\Services;

use App\Enums\AssetType;
use App\Enums\CombinationStatus;
use App\Models\SystemSetting;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use Illuminate\Validation\ValidationException;

class VehicleCombinationService
{
    public function syncForVehicle(
        Vehicle $vehicle,
        ?int $attachedPowerVehicleId,
        ?int $attachedTrailerVehicleId,
        int $userId,
    ): void {
        if ($vehicle->asset_type === AssetType::PowerVehicle) {
            $this->syncPowerVehicle($vehicle, $attachedTrailerVehicleId, $userId);

            return;
        }

        if ($vehicle->asset_type === AssetType::Trailer) {
            $this->syncTrailer($vehicle, $attachedPowerVehicleId, $userId);

            return;
        }

        $this->detachActiveCombinationAsPower($vehicle, $userId);
        $this->detachActiveCombinationAsTrailer($vehicle, $userId);
    }

    private function syncPowerVehicle(Vehicle $powerVehicle, ?int $trailerId, int $userId): void
    {
        $current = $this->activeCombinationForPower($powerVehicle);

        if (! $trailerId) {
            $this->detachCombination($current, $userId);

            return;
        }

        if ($current && $current->trailer_vehicle_id === $trailerId) {
            return;
        }

        $trailer = Vehicle::query()->findOrFail($trailerId);
        $this->assertTrailer($trailer);
        $this->assertTrailerAvailable($trailer, $current?->id);

        $this->detachCombination($current, $userId);
        $this->assertPowerVehicleCapacity($powerVehicle);
        $this->attach($powerVehicle, $trailer, $userId);
    }

    private function syncTrailer(Vehicle $trailer, ?int $powerVehicleId, int $userId): void
    {
        $current = $this->activeCombinationForTrailer($trailer);

        if (! $powerVehicleId) {
            $this->detachCombination($current, $userId);

            return;
        }

        if ($current && $current->power_vehicle_id === $powerVehicleId) {
            return;
        }

        $powerVehicle = Vehicle::query()->findOrFail($powerVehicleId);
        $this->assertPowerVehicle($powerVehicle);
        $this->assertPowerVehicleCapacity($powerVehicle, $current?->id);

        $this->detachCombination($current, $userId);
        $this->attach($powerVehicle, $trailer, $userId);
    }

    private function attach(Vehicle $powerVehicle, Vehicle $trailer, int $userId): VehicleCombination
    {
        return VehicleCombination::query()->create([
            'power_vehicle_id' => $powerVehicle->id,
            'trailer_vehicle_id' => $trailer->id,
            'attached_date' => now()->toDateString(),
            'odometer_at_attach' => $powerVehicle->odometer,
            'status' => CombinationStatus::Active,
            'attached_by' => $userId,
        ]);
    }

    private function detachActiveCombinationAsPower(Vehicle $vehicle, int $userId): void
    {
        $this->detachCombination($this->activeCombinationForPower($vehicle), $userId);
    }

    private function detachActiveCombinationAsTrailer(Vehicle $vehicle, int $userId): void
    {
        $this->detachCombination($this->activeCombinationForTrailer($vehicle), $userId);
    }

    private function detachCombination(?VehicleCombination $combination, int $userId): void
    {
        if (! $combination) {
            return;
        }

        $combination->loadMissing('powerVehicle');
        $combination->update([
            'status' => CombinationStatus::Detached,
            'detached_date' => now()->toDateString(),
            'odometer_at_detach' => $combination->powerVehicle?->odometer,
            'detached_by' => $userId,
        ]);
    }

    private function activeCombinationForPower(Vehicle $vehicle): ?VehicleCombination
    {
        return VehicleCombination::query()
            ->where('power_vehicle_id', $vehicle->id)
            ->where('status', CombinationStatus::Active)
            ->first();
    }

    private function activeCombinationForTrailer(Vehicle $vehicle): ?VehicleCombination
    {
        return VehicleCombination::query()
            ->where('trailer_vehicle_id', $vehicle->id)
            ->where('status', CombinationStatus::Active)
            ->first();
    }

    private function assertPowerVehicle(Vehicle $vehicle): void
    {
        if ($vehicle->asset_type !== AssetType::PowerVehicle) {
            throw ValidationException::withMessages([
                'attached_power_vehicle_id' => 'Select a power vehicle.',
            ]);
        }
    }

    private function assertTrailer(Vehicle $vehicle): void
    {
        if ($vehicle->asset_type !== AssetType::Trailer) {
            throw ValidationException::withMessages([
                'attached_trailer_vehicle_id' => 'Select a trailer.',
            ]);
        }
    }

    private function assertTrailerAvailable(Vehicle $trailer, ?int $ignoreCombinationId = null): void
    {
        $query = VehicleCombination::query()
            ->where('trailer_vehicle_id', $trailer->id)
            ->where('status', CombinationStatus::Active);

        if ($ignoreCombinationId) {
            $query->whereKeyNot($ignoreCombinationId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'attached_trailer_vehicle_id' => 'This trailer is already attached to a power vehicle.',
            ]);
        }
    }

    private function assertPowerVehicleCapacity(Vehicle $powerVehicle, ?int $ignoreCombinationId = null): void
    {
        $maxTrailers = (int) SystemSetting::get('max_trailers_per_power', 1);

        $query = VehicleCombination::query()
            ->where('power_vehicle_id', $powerVehicle->id)
            ->where('status', CombinationStatus::Active);

        if ($ignoreCombinationId) {
            $query->whereKeyNot($ignoreCombinationId);
        }

        if ($query->count() >= $maxTrailers) {
            throw ValidationException::withMessages([
                'attached_power_vehicle_id' => "This power vehicle already has {$maxTrailers} active trailer(s).",
                'attached_trailer_vehicle_id' => "This power vehicle already has {$maxTrailers} active trailer(s).",
            ]);
        }
    }
}
