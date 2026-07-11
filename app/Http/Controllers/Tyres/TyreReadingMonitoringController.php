<?php

namespace App\Http\Controllers\Tyres;

use App\Http\Controllers\Controller;
use App\Models\Tyre;
use App\Models\Vehicle;
use App\Services\TyreUsageTrackingService;
use App\Support\TyrePositionHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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

        $vehicles = Vehicle::query()
            ->with(['vehicleType', 'activeCombinationAsPower.trailer'])
            ->where('status', 'active')
            ->orderBy('vehicle_code')
            ->get();

        return Inertia::render('tyres/reading-monitoring/index', [
            'vehicles' => $vehicles->map(fn ($v) => [
                'id' => $v->id,
                'vehicle_code' => $v->vehicle_code,
                'plate_number' => $v->plate_number,
                'display_code' => $v->displayCodeWithPlate(),
                'asset_type' => $v->asset_type?->value,
                'vehicle_type_name' => $v->vehicleType?->name,
                'odometer' => $v->odometer,
                'attached_trailer' => $v->attachedTrailer() ? [
                    'id' => $v->attachedTrailer()->id,
                    'vehicle_code' => $v->attachedTrailer()->vehicle_code,
                    'display_code' => $v->attachedTrailer()->displayCodeWithPlate(),
                ] : null,
            ]),
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

        $tyres = Tyre::query()
            ->with(['brand:id,name', 'size:id,size_label', 'baseline', 'activeAssignment.vehicle'])
            ->where('current_location_id', $vehicle->id)
            ->where('current_location_type', '!=', 'store')
            ->orderBy('current_position_code')
            ->get();

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

        $tyres = Tyre::query()
            ->with(['brand:id,name', 'size:id,size_label', 'baseline', 'activeAssignment.vehicle', 'inspections' => fn ($q) => $q->latest()->limit(1)])
            ->where('current_location_id', $vehicle->id)
            ->where('current_location_type', '!=', 'store')
            ->orderBy('current_position_code')
            ->get();

        $mapData = $tyres->map(fn (Tyre $tyre) => $this->serializeTyreForMap($tyre, $vehicle));

        return response()->json([
            'tyres' => $mapData,
        ]);
    }

    public function getTrailerTyreMapData(Request $request, Vehicle $trailer): JsonResponse
    {
        $this->authorize('tyre.view');

        $tyres = Tyre::query()
            ->with(['brand:id,name', 'size:id,size_label', 'baseline', 'activeAssignment.vehicle', 'inspections' => fn ($q) => $q->latest()->limit(1)])
            ->where('current_location_id', $trailer->id)
            ->where('current_location_type', '!=', 'store')
            ->orderBy('current_position_code')
            ->get();

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
            'expected_life_km' => $usage['expected_life_km'],
            'total_used_km' => $usage['total_used_km'],
            'usage_percentage' => $usage['usage_percentage'],
            'estimated_remaining_percentage' => $usage['estimated_remaining_percentage'],
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
            'view_url' => route('tyres.show', $tyre->id),
            'create_baseline_url' => route('tyres.baselines.create', $tyre->id),
            'create_movement_url' => route('tyres.movements.create', ['tyre_id' => $tyre->id]),
        ];
    }

    private function calculateSummary($tyreData): array
    {
        $total = $tyreData->count();
        $healthy = $tyreData->where('status', 'Good')->count();
        $warning = $tyreData->whereIn('status', ['Watch', 'Low'])->count();
        $critical = $tyreData->whereIn('status', ['End of Life', 'Finished'])->count();
        $baselineRequired = $tyreData->where('has_baseline', false)->count();
        
        $avgRemaining = $tyreData
            ->where('estimated_remaining_percentage', '!==', null)
            ->avg('estimated_remaining_percentage');

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
