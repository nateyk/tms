<?php

namespace App\Http\Controllers;

use App\Services\TyreReportService;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalsController extends Controller
{
    public function __construct(
        private readonly TyreReportService $reportService,
    ) {}

    public function pending(): Response
    {
        $pending = $this->reportService->pendingApprovals();

        return Inertia::render('approvals/pending', [
            'movements' => $pending['movements'],
            'transfers' => $pending['transfers'],
            'disposals' => $pending['disposals'],
        ]);
    }
}
