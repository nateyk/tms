<?php

namespace App\Http\Controllers;

use App\Enums\TyreStatus;
use App\Enums\VehicleStatus;
use App\Models\Tyre;
use App\Models\Vehicle;
use App\Services\TyreReportService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TyreReportService $reportService,
    ) {}

    public function __invoke(): Response
    {
        $stats = $this->reportService->dashboardStats();

        return Inertia::render('dashboard', [
            'stats' => [
                ['label' => 'Total Tyres', 'value' => $stats['total_tyres'], 'href' => route('tyres.index')],
                ['label' => 'Active on Fleet', 'value' => $stats['active_tyres']],
                ['label' => 'In Store', 'value' => $stats['in_store']],
                ['label' => 'Pending Approvals', 'value' => $stats['pending_movements'], 'href' => route('approvals.pending')],
                ['label' => 'Pending Registration', 'value' => $stats['pending_registration'], 'href' => route('tyres.index')],
                ['label' => 'Power Units', 'value' => $stats['power_vehicles'], 'href' => route('fleet.vehicles.index')],
                ['label' => 'Trailers', 'value' => $stats['trailers'], 'href' => route('fleet.vehicles.index')],
            ],
            'todayWork' => $this->todayWork($stats),
            'tyreStatusChart' => $this->tyreStatusBreakdown(),
            'movementsTrend' => $this->reportService->completedMovementsTrend(),
            'tyresByLocation' => $this->reportService->tyresByLocationChart(),
            'fleetUtilization' => $this->reportService->fleetPositionUtilization(),
        ]);
    }

    /** @return array{labels: list<string>, data: list<int>} */
    private function tyreStatusBreakdown(): array
    {
        $counts = Tyre::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];

        foreach (\App\Enums\TyreStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);
            if ($count === 0) {
                continue;
            }
            $labels[] = $status->label();
            $data[] = $count;
        }

        return compact('labels', 'data');
    }

    /**
     * @param  array<string, int>  $stats
     * @return list<array{title: string, description: string, value: int, href: string, actionLabel: string, tone: string}>
     */
    private function todayWork(array $stats): array
    {
        $vehiclesNeedingKm = Vehicle::query()
            ->where('status', VehicleStatus::Active->value)
            ->whereNull('odometer')
            ->count();

        $tyresMissingBaseline = Tyre::query()
            ->whereIn('status', [TyreStatus::Active->value, TyreStatus::Available->value])
            ->whereDoesntHave('baseline')
            ->count();

        return [
            [
                'title' => 'Vehicles Needing KM',
                'description' => 'Start with odometer readings before tyre usage decisions.',
                'value' => $vehiclesNeedingKm,
                'href' => route('fleet.vehicles.index'),
                'actionLabel' => 'Open fleet',
                'tone' => $vehiclesNeedingKm > 0 ? 'warning' : 'success',
            ],
            [
                'title' => 'Tyres Missing Baseline',
                'description' => 'Set condition baseline for mounted or store tyres.',
                'value' => $tyresMissingBaseline,
                'href' => route('tyres.baselines.index'),
                'actionLabel' => 'Set baselines',
                'tone' => $tyresMissingBaseline > 0 ? 'warning' : 'success',
            ],
            [
                'title' => 'Pending Movements',
                'description' => 'Complete approved transfers and capture KM when needed.',
                'value' => $stats['pending_movements'],
                'href' => route('tyres.movements.index'),
                'actionLabel' => 'Review moves',
                'tone' => $stats['pending_movements'] > 0 ? 'info' : 'success',
            ],
            [
                'title' => 'Pending Approvals',
                'description' => 'Approve registrations, moves, disposals, and transfers.',
                'value' => $stats['pending_registration'] + $stats['pending_movements'],
                'href' => route('approvals.pending'),
                'actionLabel' => 'Open approvals',
                'tone' => ($stats['pending_registration'] + $stats['pending_movements']) > 0 ? 'danger' : 'success',
            ],
        ];
    }
}
