<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Models\TyreDisposal;
use App\Models\TyreMovement;
use App\Models\TrailerTransfer;
use App\Services\TyreReportService;
use Inertia\Inertia;
use Inertia\Response;

class PendingApprovalsController extends Controller
{
    public function __construct(
        private readonly TyreReportService $reportService,
    ) {}

    public function __invoke(): Response
    {
        $pending = $this->reportService->pendingApprovals();

        return Inertia::render('approvals/pending', [
            'sections' => [
                $this->section('movements', 'Tyre Movements', $pending['movements'], fn (TyreMovement $item) => [
                    'id' => $item->id,
                    'number' => $item->displayNumber(),
                    'subtitle' => ($item->tyre?->tyre_code ?? 'Tyre').' · '.$item->status->label(),
                    'status' => $item->status->value,
                    'status_label' => $item->status->label(),
                    'url' => route('tyres.movements.show', $item),
                ]),
                $this->section('transfers', 'Trailer Transfers', $pending['transfers'], fn (TrailerTransfer $item) => [
                    'id' => $item->id,
                    'number' => $item->displayNumber(),
                    'subtitle' => ($item->trailer?->vehicle_code ?? 'Trailer').' · '.$item->status->label(),
                    'status' => $item->status->value,
                    'status_label' => $item->status->label(),
                    'url' => route('fleet.trailer-transfers.show', $item),
                ]),
                $this->section('disposals', 'Tyre Disposals', $pending['disposals'], fn (TyreDisposal $item) => [
                    'id' => $item->id,
                    'number' => $item->displayNumber(),
                    'subtitle' => ($item->tyre?->tyre_code ?? 'Tyre').' · '.$item->status->label(),
                    'status' => $item->status->value,
                    'status_label' => $item->status->label(),
                    'url' => route('tyres.disposals.show', $item),
                ]),
            ],
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>  $items
     * @param  callable(mixed): array<string, mixed>  $mapper
     * @return array<string, mixed>
     */
    private function section(string $key, string $label, $items, callable $mapper): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'count' => $items->count(),
            'items' => $items->map($mapper)->values(),
        ];
    }
}
