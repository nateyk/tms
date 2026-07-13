<?php

namespace App\Http\Controllers;

use App\Services\TyreReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogsController extends Controller
{
    public function __construct(
        private readonly TyreReportService $reportService,
    ) {}

    public function index(Request $request): Response
    {
        $from = $request->query('from');
        $to = $request->query('to');

        return Inertia::render('audit-logs/index', [
            'logs' => $this->reportService->auditTrail($from, $to)->paginate(50),
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}
