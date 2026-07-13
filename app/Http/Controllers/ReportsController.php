<?php

namespace App\Http\Controllers;

use App\Services\TyreReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function __construct(
        private readonly TyreReportService $reportService,
    ) {}

    public function index(Request $request): Response
    {
        $from = $request->query('from');
        $to = $request->query('to');

        return Inertia::render('reports/index', [
            'tyreStock' => $this->reportService->tyreStock(),
            'tyreLifecycle' => $this->reportService->tyreLifecycleReport(),
            'tyreKmPerformance' => $this->reportService->tyreKmPerformanceReport(),
            'movements' => $this->reportService->movementReport($from, $to)->get(),
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}
