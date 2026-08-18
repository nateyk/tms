<?php

namespace App\Services;

use App\Models\TrailerTransfer;
use App\Models\Tyre;
use App\Models\TyreDisposal;
use App\Models\TyreMovement;
use App\Models\Vehicle;
use App\Support\TyrePositionFormatter;
use App\Support\TyrePositionHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfVoucherService
{
    public function __construct(
        private readonly TyreUsageTrackingService $usageTrackingService,
    ) {}

    public function movement(TyreMovement $movement): Response
    {
        $movement->load(['tyre.brand', 'tyre.size', 'preparedByUser', 'checkedByUser', 'approvedByUser', 'voidedByUser']);

        return $this->download(
            'pdf.vouchers.movement',
            ['movement' => $movement],
            "movement-{$movement->movement_no}.pdf"
        );
    }

    public function trailerTransfer(TrailerTransfer $transfer): Response
    {
        $transfer->load(['trailer', 'fromPowerVehicle', 'toPowerVehicle', 'preparedByUser', 'checkedByUser', 'approvedByUser', 'voidedByUser']);

        return $this->download(
            'pdf.vouchers.trailer-transfer',
            ['transfer' => $transfer],
            "trailer-transfer-{$transfer->transfer_no}.pdf"
        );
    }

    public function disposal(TyreDisposal $disposal): Response
    {
        $disposal->load(['tyre', 'preparedByUser', 'checkedByUser', 'approvedByUser', 'voidedByUser']);

        return $this->download(
            'pdf.vouchers.disposal',
            ['disposal' => $disposal],
            "disposal-{$disposal->disposal_no}.pdf"
        );
    }

    public function tyreRegistration(Tyre $tyre): Response
    {
        $tyre->load(['brand', 'size']);

        return $this->download(
            'pdf.vouchers.tyre-registration',
            ['tyre' => $tyre],
            "tyre-registration-{$tyre->tyre_code}.pdf"
        );
    }

    public function tyreHistory(Tyre $tyre): Response
    {
        $tyre->load([
            'brand',
            'size',
            'assignments' => fn ($q) => $q->latest(),
            'movements' => fn ($q) => $q->latest()->limit(50),
        ]);

        return $this->download(
            'pdf.vouchers.tyre-history',
            ['tyre' => $tyre],
            "tyre-history-{$tyre->tyre_code}.pdf"
        );
    }

    public function vehicleTyreStatus(Vehicle $vehicle): Response
    {
        $vehicle->load(['vehicleType', 'activeCombinationAsPower.trailer.vehicleType']);
        $attachedTrailer = $vehicle->attachedTrailer();
        $locationIds = array_values(array_filter([$vehicle->id, $attachedTrailer?->id]));

        $tyres = Tyre::query()
            ->with([
                'brand:id,name',
                'size:id,size_label',
                'baseline',
                'assignments:id,tyre_id,installed_date,installed_odometer,removed_date,removed_odometer,km_used,status',
                'activeAssignment.vehicle:id,vehicle_code,plate_number,odometer',
                'inspections' => fn ($query) => $query
                    ->select([
                        'id', 'tyre_id', 'vehicle_id', 'position_code', 'inspection_date',
                        'tread_depth', 'condition', 'audited_remaining_percentage',
                        'calculated_remaining_percentage_at_audit', 'audit_odometer', 'created_at',
                    ])
                    ->latest('inspection_date')
                    ->latest('created_at')
                    ->limit(1),
            ])
            ->whereIn('current_location_id', $locationIds)
            ->whereIn('current_location_type', ['power_vehicle', 'trailer'])
            ->get();

        $positionOrder = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'W', 'K', 'L', 'M', 'N', 'X', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V'];
        $tyresByPosition = $tyres->keyBy(fn (Tyre $tyre) => strtoupper((string) $tyre->current_position_code));
        $rows = collect($positionOrder)->map(function (string $position) use ($tyresByPosition, $vehicle, $attachedTrailer): array {
            /** @var Tyre|null $tyre */
            $tyre = $tyresByPosition->get($position);

            if (! $tyre) {
                return $this->emptyVehicleTyreStatusRow($position);
            }

            $usage = $this->usageTrackingService->calculateTyreUsage($tyre);
            $isTrailerTyre = $attachedTrailer && $tyre->current_location_id === $attachedTrailer->id;

            return [
                'position' => $position,
                'position_label' => TyrePositionFormatter::display($position),
                'position_type' => TyrePositionHelper::isSparePosition($position) ? 'Spare' : 'Run',
                'unit' => $isTrailerTyre ? 'Trailer' : 'Power',
                'unit_code' => ($isTrailerTyre ? $attachedTrailer : $vehicle)->displayCodeWithPlate(),
                'tyre_code' => $tyre->tyre_code,
                'serial_number' => $tyre->serial_number,
                'brand' => $tyre->brand?->name,
                'size' => $tyre->size?->size_label,
                'baseline_percentage' => $usage['baseline_percentage'],
                'calculated_percentage' => $usage['calculated_remaining_percentage'],
                'audited_percentage' => $usage['latest_audited_remaining_percentage'],
                'effective_percentage' => $usage['effective_remaining_percentage'],
                'used_km' => $usage['used_km'],
                'status' => $usage['effective_status'],
                'status_key' => $this->statusKey($usage['effective_status']),
                'audit_date' => $usage['latest_audit_date'],
                'is_empty' => false,
            ];
        });

        $mountedRows = $rows->where('is_empty', false);
        $latestOdometer = $this->usageTrackingService->getLatestVehicleOdometer($vehicle);
        $summary = [
            'configured' => $rows->count(),
            'mounted' => $mountedRows->count(),
            'empty' => $rows->where('is_empty', true)->count(),
            'good' => $mountedRows->where('status', 'Good')->count(),
            'attention' => $mountedRows->whereIn('status', ['Watch', 'Low', 'End of Life', 'Finished'])->count(),
            'no_baseline' => $mountedRows->where('status', 'Baseline Required')->count(),
        ];

        return $this->download(
            'pdf.vouchers.vehicle-tyre-status',
            compact('vehicle', 'attachedTrailer', 'rows', 'summary', 'latestOdometer'),
            "tyre-status-{$vehicle->vehicle_code}.pdf",
            'landscape',
        );
    }

    protected function download(string $view, array $data, string $filename, string $orientation = 'portrait'): Response
    {
        $pdf = Pdf::loadView($view, array_merge($data, $this->sharedViewData()))
            ->setPaper('a4', $orientation);

        return $pdf->download($filename);
    }

    private function emptyVehicleTyreStatusRow(string $position): array
    {
        return [
            'position' => $position,
            'position_label' => TyrePositionFormatter::display($position),
            'position_type' => TyrePositionHelper::isSparePosition($position) ? 'Spare' : 'Run',
            'unit' => in_array($position, ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'], true) ? 'Power' : 'Trailer',
            'unit_code' => null,
            'tyre_code' => null,
            'serial_number' => null,
            'brand' => null,
            'size' => null,
            'baseline_percentage' => null,
            'calculated_percentage' => null,
            'audited_percentage' => null,
            'effective_percentage' => null,
            'used_km' => null,
            'status' => 'Empty',
            'status_key' => 'empty',
            'audit_date' => null,
            'is_empty' => true,
        ];
    }

    private function statusKey(string $status): string
    {
        return match ($status) {
            'Good' => 'good',
            'Watch' => 'watch',
            'Low' => 'low',
            'End of Life', 'Finished' => 'critical',
            'Baseline Required' => 'no-baseline',
            default => 'empty',
        };
    }

    protected function sharedViewData(): array
    {
        return [
            'company' => 'Menkem International Business PLC',
            'companyLogoDataUri' => $this->companyLogoDataUri(),
            'printedAt' => now()->format('d M Y H:i'),
        ];
    }

    protected function companyLogoDataUri(): ?string
    {
        $path = public_path('images/menkem-logo.svg');

        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        return 'data:image/svg+xml;base64,'.base64_encode($contents);
    }
}
