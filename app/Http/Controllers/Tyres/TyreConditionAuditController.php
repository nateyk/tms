<?php

namespace App\Http\Controllers\Tyres;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tyres\StoreTyreConditionAuditRequest;
use App\Models\Tyre;
use App\Models\TyreInspection;
use App\Models\Vehicle;
use App\Services\TyreUsageTrackingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TyreConditionAuditController extends Controller
{
    public function __construct(
        private readonly TyreUsageTrackingService $usageTrackingService,
    ) {}

    public function create(Tyre $tyre): Response
    {
        $this->authorize('tyre.update');

        $tyre->load(['brand', 'size', 'activeAssignment.vehicle', 'baseline', 'inspections' => fn ($q) => $q->with(['auditedBy', 'vehicle'])->latest('inspection_date')->limit(1)]);
        $usage = $this->usageTrackingService->calculateTyreUsage($tyre);
        $latestAudit = $tyre->inspections->first();

        return Inertia::render('tyres/condition-audits/create', [
            'tyre' => [
                'id' => $tyre->id,
                'tyre_code' => $tyre->tyre_code,
                'serial_number' => $tyre->serial_number,
                'brand_name' => $tyre->brand?->name,
                'size_label' => $tyre->size?->size_label,
                'vehicle_label' => $tyre->currentVehiclePlateDisplay(),
                'position' => $tyre->currentPositionDisplay(),
                'current_tread_depth' => $tyre->current_tread_depth !== null ? (float) $tyre->current_tread_depth : null,
                'usage_summary' => $usage,
                'latest_audit' => $latestAudit ? [
                    'audited_remaining_percentage' => $latestAudit->audited_remaining_percentage !== null ? (float) $latestAudit->audited_remaining_percentage : null,
                    'inspection_date' => $latestAudit->inspection_date?->format('Y-m-d'),
                    'audit_odometer' => $latestAudit->audit_odometer,
                    'condition' => $latestAudit->condition,
                    'calculated_remaining_percentage' => $latestAudit->calculated_remaining_percentage_at_audit !== null ? (float) $latestAudit->calculated_remaining_percentage_at_audit : null,
                    'variance_percentage' => $latestAudit->audited_remaining_percentage !== null && $latestAudit->calculated_remaining_percentage_at_audit !== null
                        ? round((float) $latestAudit->audited_remaining_percentage - (float) $latestAudit->calculated_remaining_percentage_at_audit, 2)
                        : null,
                ] : null,
            ],
        ]);
    }

    public function store(StoreTyreConditionAuditRequest $request, Tyre $tyre): RedirectResponse
    {
        $tyre->load(['activeAssignment.vehicle', 'baseline', 'inspections' => fn ($q) => $q->with(['auditedBy', 'vehicle'])->latest('inspection_date')->limit(1)]);
        $usage = $this->usageTrackingService->calculateTyreUsage($tyre);
        $validated = $request->validated();
        $currentVehicle = $tyre->activeAssignment?->vehicle;
        $calculatedRemaining = $usage['calculated_remaining_percentage'];
        $variance = $calculatedRemaining !== null
            ? round((float) $validated['audited_remaining_percentage'] - (float) $calculatedRemaining, 2)
            : null;

        if (! $currentVehicle && in_array($tyre->current_location_type?->value, ['power_vehicle', 'trailer'], true)) {
            $currentVehicle = Vehicle::query()->find($tyre->current_location_id);
        }

        TyreInspection::query()->create([
            'tyre_id' => $tyre->id,
            'vehicle_id' => $currentVehicle?->id,
            'position_code' => $tyre->current_position_code,
            'inspection_date' => $validated['inspection_date'],
            'tread_depth' => $validated['tread_depth'] ?? null,
            'pressure' => null,
            'audited_remaining_percentage' => $validated['audited_remaining_percentage'],
            'calculated_remaining_percentage_at_audit' => $usage['calculated_remaining_percentage'],
            'audit_odometer' => $usage['current_vehicle_odometer'],
            'variance_percentage' => $variance,
            'condition' => $validated['condition'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'inspector' => $request->user()?->name,
            'inspected_by' => $request->user()?->id,
            'audited_by' => $request->user()?->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        if (array_key_exists('tread_depth', $validated) && $validated['tread_depth'] !== null) {
            $tyre->forceFill(['current_tread_depth' => $validated['tread_depth']])->save();
        }

        return redirect()
            ->route('tyres.show', $tyre)
            ->with('success', 'Condition audit recorded successfully.');
    }
}
