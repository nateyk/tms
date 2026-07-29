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
        $movements = collect($pending['movements'])->map(function ($movement): array {
            return [
                ...$movement->toArray(),
                'movement_type_label' => $movement->movement_type?->label() ?? 'Unknown movement',
            ];
        })->values();

        return Inertia::render('approvals/pending', [
            'movements' => $movements,
            'transfers' => $pending['transfers'],
            'disposals' => $pending['disposals'],
        ]);
    }
}
