<?php

namespace App\Services;

use App\Enums\OdometerReadingSource;
use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreBaseline;
use App\Models\Vehicle;
use App\Models\VehicleOdometerReading;
use App\Support\TyrePositionHelper;

class TyreUsageTrackingService
{
    /** @var array<int, ?int> */
    private array $latestVehicleOdometerCache = [];

    /** @var array<int, ?int> */
    private array $vehicleBaselineOdometerCache = [];

    public function calculateTyreUsage(Tyre $tyre): array
    {
        $baseline = $this->getBaselineForTyre($tyre);
        $currentVehicle = $tyre->activeAssignment?->vehicle
            ?? ($this->isVehicleLocation($tyre) ? Vehicle::query()->find($tyre->current_location_id) : null);
        $currentOdometer = $currentVehicle instanceof Vehicle
            ? $this->getLatestVehicleOdometer($currentVehicle)
            : null;

        if (!$baseline) {
            return [
                'has_baseline' => false,
                'status' => 'Baseline Required',
                'total_used_km' => null,
                'used_km' => null,
                'km_since_baseline' => null,
                'km_since_latest_audit' => null,
                'usage_percentage' => null,
                'estimated_remaining_percentage' => null,
                'calculated_remaining_percentage' => null,
                'latest_audited_remaining_percentage' => null,
                'effective_remaining_percentage' => null,
                'audit_variance_percentage' => null,
                'baseline_percentage' => null,
                'baseline_id' => null,
                'baseline_odometer' => null,
                'baseline_date' => null,
                'expected_life_km' => null,
                'current_vehicle_odometer' => $currentOdometer,
                'latest_audit_date' => null,
                'latest_audit_odometer' => null,
                'tread_depth_mm' => null,
                'audit_status' => null,
                'is_audited' => false,
                'calculated_status' => 'Baseline Required',
                'effective_status' => 'Baseline Required',
            ];
        }

        $totalUsedKm = $this->calculateTotalUsedKmSinceBaseline($tyre);
        $usagePercentage = $this->calculateUsagePercentage($tyre);
        $estimatedRemaining = $this->calculateEstimatedRemainingPercentage($tyre);
        $status = $this->getUsageStatus($estimatedRemaining, true);
        $latestInspection = $tyre->relationLoaded('inspections')
            ? $tyre->inspections->sortByDesc('inspection_date')->first()
            : $tyre->inspections()->latest('inspection_date')->first();
        $latestAuditedRemaining = $latestInspection?->audited_remaining_percentage !== null
            ? (float) $latestInspection->audited_remaining_percentage
            : null;
        $auditOdometer = $latestInspection?->audit_odometer;
        $kmSinceLatestAudit = null;

        if ($latestAuditedRemaining !== null && $auditOdometer !== null && $currentOdometer !== null && ! TyrePositionHelper::isSparePosition($tyre->current_position_code)) {
            $kmSinceLatestAudit = max(0, $currentOdometer - $auditOdometer);
        }

        $effectiveRemaining = $estimatedRemaining;

        if ($latestAuditedRemaining !== null) {
            $auditUsagePercentage = ($kmSinceLatestAudit !== null && $baseline->expected_life_km > 0)
                ? ($kmSinceLatestAudit / $baseline->expected_life_km) * 100
                : 0;
            $effectiveRemaining = round(max(0, min(100, $latestAuditedRemaining - $auditUsagePercentage)), 2);
        }

        $calculatedAtAudit = $latestInspection?->calculated_remaining_percentage_at_audit !== null
            ? (float) $latestInspection->calculated_remaining_percentage_at_audit
            : $estimatedRemaining;
        $auditVariance = $latestAuditedRemaining !== null && $calculatedAtAudit !== null
            ? round($latestAuditedRemaining - $calculatedAtAudit, 2)
            : null;
        $effectiveStatus = $this->getUsageStatus($effectiveRemaining, true);

        return [
            'has_baseline' => true,
            'status' => $effectiveStatus,
            'total_used_km' => $totalUsedKm,
            'used_km' => $totalUsedKm,
            'km_since_baseline' => $totalUsedKm,
            'km_since_latest_audit' => $kmSinceLatestAudit,
            'usage_percentage' => $usagePercentage,
            'estimated_remaining_percentage' => $estimatedRemaining,
            'calculated_remaining_percentage' => $estimatedRemaining,
            'latest_audited_remaining_percentage' => $latestAuditedRemaining,
            'effective_remaining_percentage' => $effectiveRemaining,
            'audit_variance_percentage' => $auditVariance,
            'baseline_percentage' => (float) $baseline->baseline_percentage,
            'baseline_id' => $baseline->id,
            'baseline_odometer' => $baseline->baseline_odometer,
            'baseline_date' => $baseline->baseline_date?->format('Y-m-d'),
            'expected_life_km' => $baseline->expected_life_km,
            'current_vehicle_odometer' => $currentOdometer,
            'latest_audit_date' => $latestInspection?->inspection_date?->format('Y-m-d'),
            'latest_audit_odometer' => $auditOdometer,
            'tread_depth_mm' => $latestInspection?->tread_depth !== null ? (float) $latestInspection->tread_depth : null,
            'audit_status' => $latestInspection?->condition,
            'is_audited' => $latestAuditedRemaining !== null,
            'calculated_status' => $status,
            'effective_status' => $effectiveStatus,
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

        if ($tyre->relationLoaded('assignments')) {
            return (int) $tyre->assignments
                ->filter(fn (TyreAssignment $assignment): bool => $assignment->status !== TyreAssignmentStatus::Active)
                ->filter(fn (TyreAssignment $assignment): bool =>
                    $assignment->installed_date === null
                    || $assignment->installed_date->greaterThanOrEqualTo($baseline->baseline_date)
                )
                ->sum(fn (TyreAssignment $assignment): int => (int) $assignment->km_used);
        }

        return (int) TyreAssignment::query()
            ->where('tyre_id', $tyre->id)
            ->where('status', '!=', TyreAssignmentStatus::Active)
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

        $vehicle = $activeAssignment->relationLoaded('vehicle')
            ? $activeAssignment->vehicle
            : Vehicle::query()->find($tyre->current_location_id);

        if (!$vehicle) {
            return 0;
        }

        $latestOdometer = $this->getLatestVehicleOdometer($vehicle);

        if ($latestOdometer === null) {
            return 0;
        }

        // Use the later odometer so a tyre that was baselined as a spare only
        // starts accumulating running KM from its mounted running assignment.
        $baseline = $this->getBaselineForTyre($tyre);
        $referenceOdometers = array_filter([
            $baseline?->baseline_odometer,
            $activeAssignment->installed_odometer,
            $this->getVehicleBaselineOdometer($vehicle),
        ], static fn ($odometer) => $odometer !== null);
        $referenceOdometer = $referenceOdometers === []
            ? 0
            : max($referenceOdometers);

        return max(0, $latestOdometer - $referenceOdometer);
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
        if (array_key_exists($vehicle->id, $this->latestVehicleOdometerCache)) {
            return $this->latestVehicleOdometerCache[$vehicle->id];
        }

        // Prefer latest reading from vehicle_odometer_readings
        $latestReading = VehicleOdometerReading::query()
            ->forVehicle($vehicle->id)
            ->latestReading()
            ->first();

        if ($latestReading) {
            return $this->latestVehicleOdometerCache[$vehicle->id] = $latestReading->odometer;
        }

        // Fallback to vehicles.odometer
        return $this->latestVehicleOdometerCache[$vehicle->id] = $vehicle->odometer;
    }

    public function getVehicleBaselineOdometer(Vehicle $vehicle): ?int
    {
        if (array_key_exists($vehicle->id, $this->vehicleBaselineOdometerCache)) {
            return $this->vehicleBaselineOdometerCache[$vehicle->id];
        }

        return $this->vehicleBaselineOdometerCache[$vehicle->id] = VehicleOdometerReading::query()
            ->forVehicle($vehicle->id)
            ->where('source', OdometerReadingSource::Baseline->value)
            ->latestReading()
            ->value('odometer');
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
        if ($tyre->relationLoaded('baseline')) {
            return $tyre->baseline;
        }

        return TyreBaseline::query()->forTyre($tyre->id)->first();
    }

    private function isVehicleLocation(Tyre $tyre): bool
    {
        return $tyre->current_location_id !== null
            && in_array($tyre->current_location_type, [TyreLocationType::PowerVehicle, TyreLocationType::Trailer], true);
    }
}
