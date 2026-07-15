<?php

namespace App\Services;

use App\Models\TrailerTransfer;
use App\Models\Tyre;
use App\Models\TyreDisposal;
use App\Models\TyreMovement;
use App\Models\Vehicle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfVoucherService
{
    public function movement(TyreMovement $movement): Response
    {
        $movement->load(['tyre.brand', 'tyre.size', 'preparedByUser', 'checkedByUser', 'approvedByUser']);

        return $this->download(
            'pdf.vouchers.movement',
            ['movement' => $movement],
            "movement-{$movement->movement_no}.pdf"
        );
    }

    public function trailerTransfer(TrailerTransfer $transfer): Response
    {
        $transfer->load(['trailer', 'fromPowerVehicle', 'toPowerVehicle', 'preparedByUser', 'checkedByUser', 'approvedByUser']);

        return $this->download(
            'pdf.vouchers.trailer-transfer',
            ['transfer' => $transfer],
            "trailer-transfer-{$transfer->transfer_no}.pdf"
        );
    }

    public function disposal(TyreDisposal $disposal): Response
    {
        $disposal->load(['tyre', 'preparedByUser', 'checkedByUser', 'approvedByUser']);

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
        $vehicle->load(['vehicleType', 'activeTyreAssignments.tyre.brand', 'activeTyreAssignments.tyre.size']);

        return $this->download(
            'pdf.vouchers.vehicle-tyre-status',
            ['vehicle' => $vehicle],
            "vehicle-tyre-status-{$vehicle->vehicle_code}.pdf"
        );
    }

    protected function download(string $view, array $data, string $filename): Response
    {
        $pdf = Pdf::loadView($view, array_merge($data, $this->sharedViewData()))->setPaper('a4');

        return $pdf->download($filename);
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
