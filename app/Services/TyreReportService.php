<?php

namespace App\Services;

use App\Enums\TyreAssignmentStatus;
use App\Enums\TyreLocationType;
use App\Enums\TyreStatus;
use App\Enums\VoucherStatus;
use Carbon\Carbon;
use App\Models\TrailerTransfer;
use App\Models\Tyre;
use App\Models\TyreAssignment;
use App\Models\TyreDisposal;
use App\Models\TyreMovement;
use App\Models\Vehicle;
use App\Models\VehicleCombination;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class TyreReportService
{
    public function tyreStock(): Collection
    {
        return Tyre::query()
            ->with(['brand', 'size'])
            ->orderBy('tyre_code')
            ->get();
    }

    public function tyresByVehicle(int $vehicleId): Collection
    {
        return TyreAssignment::query()
            ->with('tyre.brand', 'tyre.size')
            ->where('asset_id', $vehicleId)
            ->where('status', TyreAssignmentStatus::Active)
            ->orderBy('position_code')
            ->get();
    }

    public function tyresByVehicleReport(?int $vehicleId = null): Collection
    {
        if ($vehicleId) {
            return $this->tyresByVehicle($vehicleId);
        }

        return TyreAssignment::query()
            ->with(['tyre.brand', 'tyre.size', 'vehicle'])
            ->where('status', TyreAssignmentStatus::Active)
            ->orderBy('asset_id')
            ->orderBy('position_code')
            ->get();
    }

    public function pendingApprovals(): array
    {
        return [
            'movements' => TyreMovement::query()
                ->whereIn('status', [VoucherStatus::Submitted, VoucherStatus::Checked, VoucherStatus::Approved])
                ->with('tyre')
                ->latest()
                ->get(),
            'transfers' => TrailerTransfer::query()
                ->whereIn('status', [VoucherStatus::Submitted, VoucherStatus::Checked, VoucherStatus::Approved])
                ->with('trailer')
                ->latest()
                ->get(),
            'disposals' => TyreDisposal::query()
                ->whereIn('status', [VoucherStatus::Submitted, VoucherStatus::Checked])
                ->with('tyre')
                ->latest()
                ->get(),
        ];
    }

    public function movementReport(?string $from = null, ?string $to = null): Builder
    {
        return TyreMovement::query()
            ->with(['tyre', 'preparedByUser'])
            ->when($from, fn ($q) => $q->whereDate('movement_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('movement_date', '<=', $to))
            ->latest('movement_date');
    }

    public function disposalReport(?string $from = null, ?string $to = null): Builder
    {
        return TyreDisposal::query()
            ->with('tyre')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->latest();
    }

    public function trailerTransferHistory(?string $from = null, ?string $to = null): Builder
    {
        return TrailerTransfer::query()
            ->with(['trailer', 'fromPowerVehicle', 'toPowerVehicle'])
            ->when($from, fn ($q) => $q->whereDate('transfer_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('transfer_date', '<=', $to))
            ->latest('transfer_date');
    }

    public function auditTrail(?string $from = null, ?string $to = null): Builder
    {
        return Activity::query()
            ->with(['causer', 'subject'])
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->latest();
    }

    public function tyreKmPerformanceReport(): Collection
    {
        return Tyre::query()
            ->with('brand')
            ->orderBy('tyre_code')
            ->get()
            ->map(fn (Tyre $tyre) => [
                'tyre_code' => $tyre->tyre_code,
                'serial_number' => $tyre->serial_number,
                'brand' => $tyre->brand?->name,
                'status' => $tyre->status->label(),
                'total_km' => $tyre->totalKmUsed(),
                'purchase_price' => (float) $tyre->purchase_price,
                'cost_per_km' => $tyre->costPerKm(),
            ]);
    }

    public function tyreLifecycleReport(): Collection
    {
        return Tyre::query()
            ->with(['brand', 'size', 'assignments', 'movements', 'disposals'])
            ->orderBy('tyre_code')
            ->get()
            ->map(fn (Tyre $tyre) => [
                'tyre_code' => $tyre->tyre_code,
                'status' => $tyre->status->label(),
                'location' => $tyre->current_location_type->label(),
                'assignments_count' => $tyre->assignments->count(),
                'movements_count' => $tyre->movements->count(),
                'disposed' => $tyre->disposals->where('status', VoucherStatus::Completed)->isNotEmpty() ? 'yes' : 'no',
                'total_km' => $tyre->totalKmUsed(),
            ]);
    }

    public function dashboardStats(): array
    {
        return [
            'total_tyres' => Tyre::query()->count(),
            'active_tyres' => Tyre::query()->where('status', TyreStatus::Active)->count(),
            'in_store' => Tyre::query()->where('status', TyreStatus::Available)->count(),
            'disposed' => Tyre::query()->where('status', TyreStatus::Disposed)->count(),
            'pending_registration' => Tyre::query()->where('status', TyreStatus::PendingApproval)->count(),
            'power_vehicles' => Vehicle::query()->where('asset_type', 'power_vehicle')->where('status', 'active')->count(),
            'trailers' => Vehicle::query()->where('asset_type', 'trailer')->where('status', 'active')->count(),
            'active_combinations' => VehicleCombination::query()->where('status', 'active')->count(),
            'pending_movements' => TyreMovement::query()->whereIn('status', [
                VoucherStatus::Submitted,
                VoucherStatus::Checked,
                VoucherStatus::Approved,
            ])->count(),
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function completedMovementsTrend(int $weeks = 8): array
    {
        $currentWeek = Carbon::now()->startOfWeek();
        $firstWeek = $currentWeek->copy()->subWeeks($weeks - 1);
        $weeklyCounts = TyreMovement::query()
            ->selectRaw('YEARWEEK(completed_at, 1) as week_key, COUNT(*) as total')
            ->where('status', VoucherStatus::Completed)
            ->whereBetween('completed_at', [$firstWeek, $currentWeek->copy()->endOfWeek()])
            ->groupBy('week_key')
            ->pluck('total', 'week_key');

        $labels = [];
        $data = [];

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $start = $currentWeek->copy()->subWeeks($i);
            $labels[] = $start->format('d M');
            $data[] = (int) ($weeklyCounts[(int) $start->format('oW')] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @return array{labels: list<string>, data: list<int>, colors: list<string>}
     */
    public function tyresByLocationChart(): array
    {
        $counts = Tyre::query()
            ->selectRaw('current_location_type, count(*) as total')
            ->groupBy('current_location_type')
            ->pluck('total', 'current_location_type');

        $labels = [];
        $data = [];
        $colors = [];

        foreach (TyreLocationType::cases() as $location) {
            $count = (int) ($counts[$location->value] ?? 0);
            if ($count === 0) {
                continue;
            }
            $labels[] = $location->label();
            $data[] = $count;
            $colors[] = match ($location) {
                TyreLocationType::Store => '#2563eb',
                TyreLocationType::PowerVehicle => '#16a34a',
                TyreLocationType::Trailer => '#0891b2',
                TyreLocationType::DisposalYard => '#1f2937',
            };
        }

        return compact('labels', 'data', 'colors');
    }

    /**
     * @return array{filled: int, empty: int}
     */
    public function fleetPositionUtilization(): array
    {
        $vehicles = Vehicle::query()
            ->where('status', 'active')
            ->with('vehicleType')
            ->get();

        $assignedCounts = TyreAssignment::query()
            ->where('status', TyreAssignmentStatus::Active)
            ->whereIn('asset_id', $vehicles->pluck('id'))
            ->selectRaw('asset_id, count(*) as total')
            ->groupBy('asset_id')
            ->pluck('total', 'asset_id');

        $filled = 0;
        $total = 0;

        foreach ($vehicles as $vehicle) {
            $vehicleType = $vehicle->vehicleType;
            $positionCount = $vehicleType instanceof VehicleType
                ? count($vehicleType->positions())
                : 0;
            if ($positionCount === 0) {
                continue;
            }

            $assigned = (int) ($assignedCounts[$vehicle->id] ?? 0);

            $total += $positionCount;
            $filled += min($assigned, $positionCount);
        }

        return [
            'filled' => $filled,
            'empty' => max(0, $total - $filled),
        ];
    }
}
