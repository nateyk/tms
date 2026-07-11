<?php

namespace App\Services;

use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBaseline;
use App\Models\Vehicle;
use App\Models\VehicleOdometerReading;
use App\Support\TyrePositionHelper;

class TyreUsageTrackingService
{
    public function calculateTyreUsage(Tyre $tyre): array
    {
        $baseline = $this->getBaselineForTyre($tyre);

        if (!$baseline) {
            return [
                'has_baseline' => false,
                'status' => 'Baseline Required',
                'total_used_km' => null,
                'usage_percentage' => null,
                'estimated_remaining_percentage' => null,
                'baseline_percentage' => null,
                'expected_life_km' => null,
            ];
        }

        $totalUsedKm = $this->calculateTotalUsedKmSinceBaseline($tyre);
        $usagePercentage = $this->calculateUsagePercentage($tyre);
        $estimatedRemaining = $this->calculateEstimatedRemainingPercentage($tyre);
        $status = $this->getUsageStatus($estimatedRemaining, true);

        return [
            'has_baseline' => true,
            'status' => $status,
            'total_used_km' => $totalUsedKm,
            'usage_percentage' => $usagePercentage,
            'estimated_remaining_percentage' => $estimatedRemaining,
            'baseline_percentage' => (float) $baseline->baseline_percentage,
            'expected_life_km' => $baseline->expected_life_km,
        ];
    }

    public function calculateTotalUsedKmSinceBaseline(Tyre $tyre): ?int
    {
        $baseline = $this->getBaselineForTyre($tyre);

        if (!$baseline) {
            return null;
        }

        $closedKm = $this->calculateClosedAssignmentKmSinceBaseline($tyre);
        $activeKm = $this->calculateActiveAssignmentKm($tyre);

        return $closedKm + $activeKm;
    }

    public function calculateClosedAssignmentKmSinceBaseline(Tyre $tyre): int
    {
        $baseline = $this->getBaselineForTyre($tyre);

        if (!$baseline) {
            return 0;
        }

        return TyreAssignment::query()
            ->where('tyre_id', $tyre->id)
            ->where('status', '!=', 'active')
            ->where(function ($query) use ($baseline) {
                $query->where('installed_date', '>=', $baseline->baseline_date)
                    ->orWhereNull('installed_date');
            })
            ->sum('km_used');
    }

    public function calculateActiveAssignmentKm(Tyre $tyre): int
    {
        if ($tyre->isDisposed() || $tyre->current_location_type === null) {
            return 0;
        }

        // Tyre in store does not accumulate active KM
        if ($tyre->current_location_type->value === 'store') {
            return 0;
        }

        // Spare tyres do not accumulate running KM
        if (TyrePositionHelper::isSparePosition($tyre->current_position_code)) {
            return 0;
        }

        $activeAssignment = $tyre->activeAssignment;

        if (!$activeAssignment) {
            return 0;
        }

        $vehicle = Vehicle::query()->find($tyre->current_location_id);

        if (!$vehicle) {
            return 0;
        }

        $latestOdometer = $this->getLatestVehicleOdometer($vehicle);

        if ($latestOdometer === null) {
            return 0;
        }

        $installedOdometer = $activeAssignment->installed_odometer ?? 0;

        return max(0, $latestOdometer - $installedOdometer);
    }

    public function calculateUsagePercentage(Tyre $tyre): ?float
    {
        $baseline = $this->getBaselineForTyre($tyre);

        if (!$baseline) {
            return null;
        }

        $totalUsedKm = $this->calculateTotalUsedKmSinceBaseline($tyre);

        if ($totalUsedKm === null || $baseline->expected_life_km === 0) {
            return 0.0;
        }

        return ($totalUsedKm / $baseline->expected_life_km) * 100;
    }

    public function calculateEstimatedRemainingPercentage(Tyre $tyre): ?float
    {
        $baseline = $this->getBaselineForTyre($tyre);

        if (!$baseline) {
            return null;
        }

        $usagePercentage = $this->calculateUsagePercentage($tyre);

        if ($usagePercentage === null) {
            return null;
        }

        $estimatedRemaining = (float) $baseline->baseline_percentage - $usagePercentage;

        // Clamp: cannot go below 0
        $estimatedRemaining = max(0, $estimatedRemaining);

        // Clamp: cannot go above baseline percentage
        $estimatedRemaining = min($estimatedRemaining, (float) $baseline->baseline_percentage);

        return round($estimatedRemaining, 2);
    }

    public function getUsageStatus(?float $estimatedRemaining, bool $hasBaseline): string
    {
        if (!$hasBaseline) {
            return 'Baseline Required';
        }

        if ($estimatedRemaining === null) {
            return 'Unknown';
        }

        if ($estimatedRemaining >= 60) {
            return 'Good';
        }

        if ($estimatedRemaining >= 30) {
            return 'Watch';
        }

        if ($estimatedRemaining >= 10) {
            return 'Low';
        }

        if ($estimatedRemaining > 0) {
            return 'End of Life';
        }

        return 'Finished';
    }

    public function getLatestVehicleOdometer(Vehicle $vehicle): ?int
    {
        // Prefer latest reading from vehicle_odometer_readings
        $latestReading = VehicleOdometerReading::query()
            ->forVehicle($vehicle->id)
            ->latest()
            ->first();

        if ($latestReading) {
            return $latestReading->odometer;
        }

        // Fallback to vehicles.odometer
        return $vehicle->odometer;
    }

    public function getUsageHistory(Tyre $tyre): \Illuminate\Support\Collection
    {
        return TyreAssignment::query()
            ->where('tyre_id', $tyre->id)
            ->with(['vehicle', 'movement'])
            ->orderBy('installed_date')
            ->get()
            ->map(function (TyreAssignment $assignment) {
                return [
                    'id' => $assignment->id,
                    'vehicle_code' => $assignment->vehicle?->vehicle_code,
                    'vehicle_plate' => $assignment->vehicle?->plate_number,
                    'position_code' => $assignment->position_code,
                    'installed_odometer' => $assignment->installed_odometer,
                    'removed_odometer' => $assignment->removed_odometer,
                    'km_used' => $assignment->km_used,
                    'installed_date' => $assignment->installed_date?->format('Y-m-d'),
                    'removed_date' => $assignment->removed_date?->format('Y-m-d'),
                    'status' => $assignment->status,
                    'movement_id' => $assignment->movement_id,
                    'is_active' => $assignment->status === 'active',
                ];
            });
    }

    public function getBaselineForTyre(Tyre $tyre): ?TyreBaseline
    {
        return TyreBaseline::query()->forTyre($tyre->id)->first();
    }
}
