<?php

namespace App\Http\Controllers\Approvals;

use App\Http\Controllers\Controller;
use App\Models\TyreDisposal;
use App\Models\TyreMovement;
use App\Models\TrailerTransfer;
use App\Models\Vehicle;
use App\Services\TyreReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function __construct(
        private readonly TyreReportService $reportService,
    ) {}

    public function index(): Response
    {
        abort_unless(request()->user()?->can('report.view'), 403);

        return Inertia::render('approvals/reports', [
            'vehicles' => Vehicle::query()
                ->orderBy('vehicle_code')
                ->get(['id', 'vehicle_code'])
                ->map(fn (Vehicle $vehicle) => [
                    'id' => $vehicle->id,
                    'label' => $vehicle->vehicle_code,
                ]),
            'filters' => [
                'date_from' => now()->subMonth()->toDateString(),
                'date_to' => now()->toDateString(),
                'vehicle_id' => null,
            ],
            'canExport' => request()->user()?->can('report.export') ?? false,
            'canExportAudit' => request()->user()?->can('audit.view') ?? false,
        ]);
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        abort_unless($request->user()?->can('report.export'), 403);

        $from = $request->query('date_from');
        $to = $request->query('date_to');
        $vehicleId = $request->query('vehicle_id') ? (int) $request->query('vehicle_id') : null;

        return match ($type) {
            'stock' => $this->exportStock(),
            'movements' => $this->exportMovements($from, $to),
            'disposals' => $this->exportDisposals($from, $to),
            'trailer-transfers' => $this->exportTrailerTransfers($from, $to),
            'tyres-by-vehicle' => $this->exportTyresByVehicle($vehicleId),
            'km-performance' => $this->exportKmPerformance(),
            'lifecycle' => $this->exportLifecycle(),
            'audit-trail' => $this->exportAuditTrail($request, $from, $to),
            default => abort(404),
        };
    }

    private function exportStock(): StreamedResponse
    {
        $rows = $this->reportService->tyreStock();

        return $this->csvDownload('tyre-stock.csv', [
            ['Tyre Code', 'Serial', 'Brand', 'Size', 'Status', 'Location Type', 'Location ID', 'Position'],
            ...$rows->map(fn ($tyre) => [
                $tyre->tyre_code,
                $tyre->serial_number,
                $tyre->brand?->name,
                $tyre->size?->size_label,
                $tyre->status->value,
                $tyre->current_location_type->value,
                $tyre->current_location_id,
                $tyre->currentPositionDisplay(),
            ]),
        ]);
    }

    private function exportMovements(?string $from, ?string $to): StreamedResponse
    {
        $rows = $this->reportService->movementReport($from, $to)->get();

        return $this->csvDownload('tyre-movements.csv', [
            ['Movement No', 'Tyre', 'Type', 'Date', 'Status', 'From', 'To'],
            ...$rows->map(fn (TyreMovement $movement) => [
                $movement->movement_no,
                $movement->tyre?->tyre_code,
                $movement->movement_type->value,
                $movement->movement_date?->format('Y-m-d'),
                $movement->status->value,
                $movement->from_location_type?->value,
                $movement->to_location_type?->value,
            ]),
        ]);
    }

    private function exportDisposals(?string $from, ?string $to): StreamedResponse
    {
        $rows = $this->reportService->disposalReport($from, $to)->get();

        return $this->csvDownload('tyre-disposals.csv', [
            ['No', 'Tyre', 'Reason', 'Status', 'Scrap Value', 'Sold Amount'],
            ...$rows->map(fn (TyreDisposal $record) => [
                $record->disposal_no,
                $record->tyre?->tyre_code,
                $record->disposal_reason->value,
                $record->status->value,
                $record->estimated_scrap_value,
                $record->sold_amount,
            ]),
        ]);
    }

    private function exportTrailerTransfers(?string $from, ?string $to): StreamedResponse
    {
        $rows = $this->reportService->trailerTransferHistory($from, $to)->get();

        return $this->csvDownload('trailer-transfers.csv', [
            ['Transfer No', 'Trailer', 'From Power', 'To Power', 'Date', 'Status'],
            ...$rows->map(fn (TrailerTransfer $record) => [
                $record->transfer_no,
                $record->trailer?->vehicle_code,
                $record->fromPowerVehicle?->vehicle_code,
                $record->toPowerVehicle?->vehicle_code,
                $record->transfer_date?->format('Y-m-d'),
                $record->status->value,
            ]),
        ]);
    }

    private function exportTyresByVehicle(?int $vehicleId): StreamedResponse
    {
        $rows = $this->reportService->tyresByVehicleReport($vehicleId);

        return $this->csvDownload('tyres-by-vehicle.csv', [
            ['Vehicle ID', 'Position', 'Tyre Code', 'Serial', 'Brand', 'Status'],
            ...$rows->map(fn ($assignment) => [
                $assignment->asset_id,
                $assignment->positionDisplay(),
                $assignment->tyre?->tyre_code,
                $assignment->tyre?->serial_number,
                $assignment->tyre?->brand?->name,
                $assignment->tyre?->status?->value,
            ]),
        ]);
    }

    private function exportKmPerformance(): StreamedResponse
    {
        $rows = $this->reportService->tyreKmPerformanceReport();

        return $this->csvDownload('tyre-km-performance.csv', [
            ['Tyre Code', 'Serial', 'Brand', 'Status', 'Total KM', 'Purchase Price', 'Cost Per KM'],
            ...$rows->map(fn ($row) => array_values($row)),
        ]);
    }

    private function exportLifecycle(): StreamedResponse
    {
        $rows = $this->reportService->tyreLifecycleReport();

        return $this->csvDownload('tyre-lifecycle.csv', [
            ['Tyre Code', 'Status', 'Location', 'Assignments', 'Movements', 'Disposed', 'Total KM'],
            ...$rows->map(fn ($row) => array_values($row)),
        ]);
    }

    private function exportAuditTrail(Request $request, ?string $from, ?string $to): StreamedResponse
    {
        abort_unless($request->user()?->can('audit.view'), 403);

        $rows = $this->reportService->auditTrail($from, $to)->get();

        return $this->csvDownload('audit-trail.csv', [
            ['Date', 'Event', 'Description', 'Subject', 'Causer'],
            ...$rows->map(fn (Activity $record) => [
                $record->created_at?->format('Y-m-d H:i'),
                $record->event,
                $record->description,
                $record->subject_type ? class_basename($record->subject_type).' #'.$record->subject_id : '',
                $record->causer?->name ?? $record->causer_id,
            ]),
        ]);
    }

    /** @param  array<int, array<int, mixed>>  $rows */
    private function csvDownload(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
