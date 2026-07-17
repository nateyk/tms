<?php

namespace App\Http\Controllers\Tyres;

use App\Http\Controllers\Controller;
use App\Models\Tyre;
use App\Models\Vehicle;
use App\Services\TyreUsageTrackingService;
use App\Support\TyrePositionHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class TyreReadingMonitoringController extends Controller
{
    public function __construct(
        private readonly TyreUsageTrackingService $usageTrackingService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('tyre.view');

        // Get active vehicle combinations (power vehicles with trailers)
        $combinations = \App\Models\VehicleCombination::query()
            ->with(['powerVehicle.vehicleType', 'trailer.vehicleType'])
            ->where('status', 'active')
            ->orderBy('power_vehicle_id')
            ->get();

        // Get standalone power vehicles (not in combinations)
        $standalonePowerVehicles = Vehicle::query()
            ->with(['vehicleType'])
            ->where('status', 'active')
            ->where('asset_type', 'power_vehicle')
            ->whereDoesntHave('activeCombinationAsPower')
            ->orderBy('vehicle_code')
            ->get();

        // Get standalone trailers (not in combinations)
        $standaloneTrailers = Vehicle::query()
            ->with(['vehicleType'])
            ->where('status', 'active')
            ->where('asset_type', 'trailer')
            ->whereDoesntHave('activeCombinationAsTrailer')
            ->orderBy('vehicle_code')
            ->get();

        $combinationsData = $combinations->map(fn ($c) => [
            'type' => 'combination',
            'id' => $c->powerVehicle->id,
            'power_vehicle' => [
                'id' => $c->powerVehicle->id,
                'vehicle_code' => $c->powerVehicle->vehicle_code,
                'plate_number' => $c->powerVehicle->plate_number,
                'display_code' => $c->powerVehicle->displayCodeWithPlate(),
                'vehicle_type_name' => $c->powerVehicle->vehicleType?->name,
                'odometer' => $c->powerVehicle->odometer,
            ],
            'trailer' => $c->trailer ? [
                'id' => $c->trailer->id,
                'vehicle_code' => $c->trailer->vehicle_code,
                'plate_number' => $c->trailer->plate_number,
                'display_code' => $c->trailer->displayCodeWithPlate(),
                'vehicle_type_name' => $c->trailer->vehicleType?->name,
            ] : null,
        ]);

        $standalonePowerData = $standalonePowerVehicles->map(fn ($v) => [
            'type' => 'standalone_power',
            'id' => $v->id,
            'vehicle_code' => $v->vehicle_code,
            'plate_number' => $v->plate_number,
            'display_code' => $v->displayCodeWithPlate(),
            'vehicle_type_name' => $v->vehicleType?->name,
            'odometer' => $v->odometer,
        ]);

        $standaloneTrailerData = $standaloneTrailers->map(fn ($v) => [
            'type' => 'standalone_trailer',
            'id' => $v->id,
            'vehicle_code' => $v->vehicle_code,
            'plate_number' => $v->plate_number,
            'display_code' => $v->displayCodeWithPlate(),
            'vehicle_type_name' => $v->vehicleType?->name,
        ]);

        $allVehicles = $combinationsData->concat($standalonePowerData)->concat($standaloneTrailerData);

        return Inertia::render('tyres/reading-monitoring/index', [
            'vehicles' => $allVehicles,
        ]);
    }

    public function show(Request $request, Vehicle $vehicle): Response
    {
        $this->authorize('tyre.view');

        $vehicle->load(['vehicleType', 'activeCombinationAsPower.trailer']);
        $attachedTrailer = $vehicle->attachedTrailer();
        
        if ($attachedTrailer) {
            $attachedTrailer->load('vehicleType');
        }

        $tyres = $this->tyresForVehicle($vehicle);

        $tyreData = $tyres->map(fn (Tyre $tyre) => $this->serializeTyreForMap($tyre, $vehicle));

        $summary = $this->calculateSummary($tyreData);

        return Inertia::render('tyres/reading-monitoring/vehicle', [
            'vehicle' => [
                'id' => $vehicle->id,
                'vehicle_code' => $vehicle->vehicle_code,
                'plate_number' => $vehicle->plate_number,
                'display_code' => $vehicle->displayCodeWithPlate(),
                'asset_type' => $vehicle->asset_type?->value,
                'vehicle_type_name' => $vehicle->vehicleType?->name,
                'odometer' => $vehicle->odometer,
                'vehicle_type' => $vehicle->vehicleType,
            ],
            'attached_trailer' => $attachedTrailer ? [
                'id' => $attachedTrailer->id,
                'vehicle_code' => $attachedTrailer->vehicle_code,
                'display_code' => $attachedTrailer->displayCodeWithPlate(),
                'asset_type' => $attachedTrailer->asset_type?->value,
                'vehicle_type_name' => $attachedTrailer->vehicleType?->name,
                'odometer' => $attachedTrailer->odometer,
                'vehicle_type' => $attachedTrailer->vehicleType,
            ] : null,
            'tyres' => $tyreData,
            'summary' => $summary,
        ]);
    }

    public function getVehicleTyreMapData(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('tyre.view');

        $tyres = $this->tyresForVehicle($vehicle);

        $mapData = $tyres->map(fn (Tyre $tyre) => $this->serializeTyreForMap($tyre, $vehicle));

        return response()->json([
            'tyres' => $mapData,
        ]);
    }

    public function getTrailerTyreMapData(Request $request, Vehicle $trailer): JsonResponse
    {
        $this->authorize('tyre.view');

        $tyres = $this->tyresForVehicle($trailer);

        $mapData = $tyres->map(fn (Tyre $tyre) => $this->serializeTyreForMap($tyre, $trailer));

        return response()->json([
            'tyres' => $mapData,
        ]);
    }

    private function serializeTyreForMap(Tyre $tyre, Vehicle $vehicle): array
    {
        $usage = $this->usageTrackingService->calculateTyreUsage($tyre);
        $latestInspection = $tyre->inspections->first();

        return [
            'id' => $tyre->id,
            'tyre_code' => $tyre->tyre_code,
            'serial_number' => $tyre->serial_number,
            'brand_name' => $tyre->brand?->name,
            'size_label' => $tyre->size?->size_label,
            'pattern' => $tyre->pattern,
            'current_position_code' => $tyre->current_position_code,
            'position_display' => $tyre->currentPositionDisplay(),
            'position_type' => TyrePositionHelper::getPositionType($tyre->current_position_code),
            'spare_label' => TyrePositionHelper::isSparePosition($tyre->current_position_code) 
                ? TyrePositionHelper::spareLabel($tyre->current_position_code) 
                : null,
            'has_baseline' => $usage['has_baseline'],
            'baseline_percentage' => $usage['baseline_percentage'],
            'baseline_id' => $usage['baseline_id'],
            'baseline_odometer' => $usage['baseline_odometer'],
            'baseline_date' => $usage['baseline_date'],
            'expected_life_km' => $usage['expected_life_km'],
            'total_used_km' => $usage['total_used_km'],
            'used_km' => $usage['used_km'],
            'km_since_baseline' => $usage['km_since_baseline'],
            'km_since_latest_audit' => $usage['km_since_latest_audit'],
            'usage_percentage' => $usage['usage_percentage'],
            'estimated_remaining_percentage' => $usage['estimated_remaining_percentage'],
            'calculated_remaining_percentage' => $usage['calculated_remaining_percentage'],
            'latest_audited_remaining_percentage' => $usage['latest_audited_remaining_percentage'],
            'effective_remaining_percentage' => $usage['effective_remaining_percentage'],
            'audit_variance_percentage' => $usage['audit_variance_percentage'],
            'latest_audit_date' => $usage['latest_audit_date'],
            'latest_audit_odometer' => $usage['latest_audit_odometer'],
            'latest_audit' => $latestInspection ? [
                'id' => $latestInspection->id,
                'audited_remaining_percentage' => $latestInspection->audited_remaining_percentage !== null ? (float) $latestInspection->audited_remaining_percentage : null,
                'calculated_remaining_percentage' => $latestInspection->calculated_remaining_percentage_at_audit !== null ? (float) $latestInspection->calculated_remaining_percentage_at_audit : null,
                'variance_percentage' => $latestInspection->audited_remaining_percentage !== null && $latestInspection->calculated_remaining_percentage_at_audit !== null
                    ? round((float) $latestInspection->audited_remaining_percentage - (float) $latestInspection->calculated_remaining_percentage_at_audit, 2)
                    : null,
                'odometer_km' => $latestInspection->audit_odometer,
                'audit_date' => $latestInspection->inspection_date?->format('Y-m-d'),
                'recorded_at' => $latestInspection->created_at?->toDateTimeString(),
                'recorded_by_name' => $latestInspection->auditedBy?->name ?? $latestInspection->inspector,
                'vehicle_code' => $latestInspection->vehicle?->displayCodeWithPlate(),
                'position_code' => $latestInspection->position_code,
                'tread_depth_mm' => $latestInspection->tread_depth !== null ? (float) $latestInspection->tread_depth : null,
                'condition_status' => $latestInspection->condition,
                'reason' => $latestInspection->reason,
                'notes' => $latestInspection->notes,
            ] : null,
            'tread_depth_mm' => $usage['tread_depth_mm'],
            'audit_status' => $usage['audit_status'],
            'is_audited' => $usage['is_audited'],
            'calculated_status' => $usage['calculated_status'],
            'effective_status' => $usage['effective_status'],
            'current_vehicle_odometer' => $usage['current_vehicle_odometer'],
            'status' => $usage['status'],
            'status_color' => $this->getStatusColor($usage['status']),
            'installed_odometer' => $tyre->activeAssignment?->installed_odometer,
            'installed_date' => $tyre->activeAssignment?->installed_date?->format('Y-m-d'),
            'latest_inspection' => $latestInspection ? [
                'tread_depth' => $latestInspection->tread_depth,
                'pressure' => $latestInspection->pressure,
                'condition' => $latestInspection->condition,
                'inspection_date' => $latestInspection->inspection_date?->format('Y-m-d'),
                'inspector' => $latestInspection->inspector,
            ] : null,
            'usage_summary' => $usage,
            'view_url' => route('tyres.show', $tyre->id),
            'create_baseline_url' => route('tyres.baselines.create', ['tyre_id' => $tyre->id]),
            'view_baseline_url' => $usage['baseline_id'] ? route('tyres.baselines.show', $usage['baseline_id']) : null,
            'record_audit_url' => route('tyres.condition-audits.create', $tyre->id),
            'create_movement_url' => route('tyres.movements.create', [
                'tyre_id' => $tyre->id,
                'source_vehicle_id' => $vehicle->id,
                'source_position' => $tyre->current_position_code,
                'source_location_type' => 'vehicle',
                'movement_context' => 'vehicle_map',
            ]),
        ];
    }

    /** @return Collection<int, Tyre> */
    private function tyresForVehicle(Vehicle $vehicle): Collection
    {
        return Tyre::query()
            ->with([
                'brand:id,name',
                'size:id,size_label',
                'baseline',
                'assignments:id,tyre_id,installed_date,installed_odometer,km_used,status',
                'activeAssignment.vehicle:id,vehicle_code,plate_number,odometer',
                'inspections' => fn ($query) => $query
                    ->select([
                        'id', 'tyre_id', 'vehicle_id', 'position_code', 'inspection_date',
                        'tread_depth', 'condition', 'inspector', 'audited_by', 'notes',
                        'audited_remaining_percentage', 'calculated_remaining_percentage_at_audit',
                        'audit_odometer', 'reason', 'created_at',
                    ])
                    ->with(['auditedBy:id,name', 'vehicle:id,vehicle_code,plate_number'])
                    ->latest('inspection_date')
                    ->latest('created_at')
                    ->limit(1),
            ])
            ->where('current_location_id', $vehicle->id)
            ->whereIn('current_location_type', ['power_vehicle', 'trailer'])
            ->orderBy('current_position_code')
            ->get();
    }

    private function calculateSummary($tyreData): array
    {
        $total = $tyreData->count();
        $healthy = $tyreData->where('status', 'Good')->count();
        $warning = $tyreData->whereIn('status', ['Watch', 'Low'])->count();
        $critical = $tyreData->whereIn('status', ['End of Life', 'Finished'])->count();
        $baselineRequired = $tyreData->where('has_baseline', false)->count();
        
        $avgRemaining = $tyreData
            ->where('effective_remaining_percentage', '!==', null)
            ->avg('effective_remaining_percentage');

        return [
            'total' => $total,
            'healthy' => $healthy,
            'warning' => $warning,
            'critical' => $critical,
            'baseline_required' => $baselineRequired,
            'average_remaining_percentage' => $avgRemaining ? round($avgRemaining, 1) : null,
        ];
    }

    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'Baseline Required' => 'gray',
            'Good' => 'green',
            'Watch' => 'yellow',
            'Low' => 'orange',
            'End of Life' => 'red',
            'Finished' => 'gray',
            default => 'gray',
        };
    }
}
