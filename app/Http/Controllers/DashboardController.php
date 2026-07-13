<?php

namespace App\Http\Controllers;

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
            'tyreStatusChart' => $this->tyreStatusBreakdown(),
            'movementsTrend' => $this->reportService->completedMovementsTrend(),
            'tyresByLocation' => $this->reportService->tyresByLocationChart(),
            'fleetUtilization' => $this->reportService->fleetPositionUtilization(),
        ]);
    }

    /** @return array{labels: list<string>, data: list<int>} */
    private function tyreStatusBreakdown(): array
    {
        $counts = \App\Models\Tyre::query()
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
}
